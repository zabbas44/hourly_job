<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\Worker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class BackupService
{
    public function createSnapshot(string $trigger = 'manual'): string
    {
        $timestamp = now();
        $filename = 'backup-'.$timestamp->format('Y-m-d_H-i-s').'.json';
        $path = $this->snapshotDirectory().DIRECTORY_SEPARATOR.$filename;

        File::ensureDirectoryExists($this->snapshotDirectory());

        File::put($path, json_encode([
            'meta' => [
                'created_at' => $timestamp->toIso8601String(),
                'trigger' => $trigger,
                'app' => 'learning-system',
            ],
            'workers' => Worker::query()->orderBy('id')->get()->map->getAttributes()->all(),
            'projects' => Project::query()->orderBy('id')->get()->map->getAttributes()->all(),
            'project_worker' => DB::table('project_worker')->orderBy('project_id')->orderBy('worker_id')->get()->map(fn ($row) => (array) $row)->all(),
            'time_entries' => TimeEntry::query()->orderBy('id')->get()->map->getAttributes()->all(),
            'payments' => Payment::query()->orderBy('id')->get()->map->getAttributes()->all(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $filename;
    }

    public function listSnapshots(): array
    {
        File::ensureDirectoryExists($this->snapshotDirectory());

        return collect(File::files($this->snapshotDirectory()))
            ->filter(fn ($file) => $file->getExtension() === 'json')
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->map(fn ($file) => [
                'name' => $file->getFilename(),
                'path' => $file->getPathname(),
                'size' => $file->getSize(),
                'modified_at' => date('Y-m-d H:i:s', $file->getMTime()),
            ])
            ->values()
            ->all();
    }

    public function snapshotPath(string $name): string
    {
        $safeName = basename($name);
        $path = $this->snapshotDirectory().DIRECTORY_SEPARATOR.$safeName;

        if (! File::exists($path)) {
            throw new RuntimeException('Backup file not found.');
        }

        return $path;
    }

    public function restore(string $name, string $scope = 'all', array $filters = []): void
    {
        $snapshot = json_decode(File::get($this->snapshotPath($name)), true, flags: JSON_THROW_ON_ERROR);

        DB::transaction(function () use ($snapshot, $scope, $filters): void {
            Schema::disableForeignKeyConstraints();

            if ($scope === 'all') {
                DB::table('project_worker')->truncate();
                Payment::query()->truncate();
                TimeEntry::query()->truncate();
                Project::query()->truncate();
                Worker::query()->truncate();

                $this->insertRows('workers', $snapshot['workers'] ?? []);
                $this->insertRows('projects', $snapshot['projects'] ?? []);
                $this->insertRows('time_entries', $snapshot['time_entries'] ?? []);
                $this->insertRows('payments', $snapshot['payments'] ?? []);
                $this->insertRows('project_worker', $snapshot['project_worker'] ?? []);

                Schema::enableForeignKeyConstraints();

                return;
            }

            $workerId = isset($filters['worker_id']) ? (int) $filters['worker_id'] : null;
            $projectId = isset($filters['project_id']) ? (int) $filters['project_id'] : null;
            $workDate = $filters['work_date'] ?? null;

            $workers = collect($snapshot['workers'] ?? []);
            $projects = collect($snapshot['projects'] ?? []);
            $projectWorker = collect($snapshot['project_worker'] ?? []);
            $timeEntries = collect($snapshot['time_entries'] ?? []);
            $payments = collect($snapshot['payments'] ?? []);

            if ($scope === 'workers') {
                $workerRows = $workerId ? $workers->where('id', $workerId)->values() : $workers->values();
                $ids = $workerRows->pluck('id')->all();
                Payment::query()->whereIn('worker_id', $ids)->delete();
                TimeEntry::query()->whereIn('worker_id', $ids)->delete();
                DB::table('project_worker')->whereIn('worker_id', $ids)->delete();
                Worker::query()->whereIn('id', $ids)->delete();
                $this->insertRows('workers', $workerRows->all());
                $this->insertRows('project_worker', $projectWorker->whereIn('worker_id', $ids)->values()->all());
                $this->insertRows('payments', $payments->whereIn('worker_id', $ids)->values()->all());
                $this->insertRows('time_entries', $timeEntries->whereIn('worker_id', $ids)->values()->all());
            }

            if ($scope === 'projects') {
                $projectRows = $projectId ? $projects->where('id', $projectId)->values() : $projects->values();
                $ids = $projectRows->pluck('id')->all();
                $relatedWorkerIds = $projectWorker->whereIn('project_id', $ids)->pluck('worker_id')->unique()->all();
                $this->upsertRows('workers', $workers->whereIn('id', $relatedWorkerIds)->values()->all(), ['id']);
                TimeEntry::query()->whereIn('project_id', $ids)->delete();
                DB::table('project_worker')->whereIn('project_id', $ids)->delete();
                Project::query()->whereIn('id', $ids)->delete();
                $this->insertRows('projects', $projectRows->all());
                $this->insertRows('project_worker', $projectWorker->whereIn('project_id', $ids)->values()->all());
                $this->insertRows('time_entries', $timeEntries->whereIn('project_id', $ids)->values()->all());
            }

            if ($scope === 'time_entries') {
                $entryRows = $timeEntries->filter(function (array $row) use ($workerId, $projectId, $workDate): bool {
                    if ($workerId && (int) $row['worker_id'] !== $workerId) {
                        return false;
                    }

                    if ($projectId && (int) $row['project_id'] !== $projectId) {
                        return false;
                    }

                    if ($workDate && $row['work_date'] !== $workDate) {
                        return false;
                    }

                    return true;
                })->values();

                $this->upsertRows('workers', $workers->whereIn('id', $entryRows->pluck('worker_id')->unique()->all())->values()->all(), ['id']);
                $this->upsertRows('projects', $projects->whereIn('id', $entryRows->pluck('project_id')->unique()->all())->values()->all(), ['id']);

                $query = TimeEntry::query();
                if ($workerId) {
                    $query->where('worker_id', $workerId);
                }
                if ($projectId) {
                    $query->where('project_id', $projectId);
                }
                if ($workDate) {
                    $query->whereDate('work_date', $workDate);
                }
                $query->delete();

                $this->insertRows('time_entries', $entryRows->all());
            }

            if ($scope === 'payments') {
                $paymentRows = $payments->filter(function (array $row) use ($workerId): bool {
                    return ! $workerId || (int) $row['worker_id'] === $workerId;
                })->values();
                $workerIds = $paymentRows->pluck('worker_id')->unique()->all();
                $this->upsertRows('workers', $workers->whereIn('id', $workerIds)->values()->all(), ['id']);
                Payment::query()->whereIn('worker_id', $workerIds)->delete();
                $this->insertRows('payments', $paymentRows->all());
            }

            Schema::enableForeignKeyConstraints();
        });
    }

    private function snapshotDirectory(): string
    {
        return storage_path('app/backups/snapshots');
    }

    private function insertRows(string $table, array $rows): void
    {
        if ($rows === []) {
            return;
        }

        DB::table($table)->insert($rows);
    }

    private function upsertRows(string $table, array $rows, array $uniqueBy): void
    {
        if ($rows === []) {
            return;
        }

        $columns = array_keys($rows[0]);
        DB::table($table)->upsert($rows, $uniqueBy, $columns);
    }
}
