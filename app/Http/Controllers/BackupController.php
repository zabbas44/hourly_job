<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Worker;
use App\Services\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;

class BackupController extends Controller
{
    public function __construct(private readonly BackupService $backups)
    {
    }

    public function index(): View
    {
        return view('backups.index', [
            'backups' => $this->backups->listSnapshots(),
            'workers' => Worker::orderBy('name')->get(['id', 'name']),
            'projects' => Project::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(): RedirectResponse
    {
        $filename = $this->backups->createSnapshot('manual');

        return redirect()->route('backups.index')->with('status', 'Backup created successfully: '.$filename);
    }

    public function download(string $backup)
    {
        return Response::download($this->backups->snapshotPath($backup));
    }

    public function restore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'backup' => ['required', 'string'],
            'scope' => ['required', 'in:all,workers,projects,time_entries,payments'],
            'worker_id' => ['nullable', 'integer', 'exists:workers,id'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'work_date' => ['nullable', 'date'],
        ]);

        $this->backups->restore($validated['backup'], $validated['scope'], $validated);

        return redirect()->route('backups.index')->with('status', 'Backup restored successfully.');
    }

    public function exportWorkersCsv()
    {
        return Response::streamDownload(function (): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['name', 'phone', 'email', 'bank_title', 'account_number', 'rate_amount', 'rate_type']);

            foreach (Worker::orderBy('name')->get() as $worker) {
                fputcsv($handle, [
                    $worker->name,
                    $worker->phone,
                    $worker->email,
                    $worker->bank_title,
                    $worker->account_number,
                    $worker->hourly_rate,
                    $worker->rate_type,
                ]);
            }

            fclose($handle);
        }, 'workers-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function importWorkersCsv(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'workers_csv' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $rows = array_map('str_getcsv', file($validated['workers_csv']->getRealPath()));
        $header = array_map('trim', array_shift($rows) ?? []);

        foreach ($rows as $row) {
            if (count(array_filter($row, fn ($value) => $value !== null && $value !== '')) === 0) {
                continue;
            }

            $data = array_combine($header, $row);

            Worker::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'bank_title' => $data['bank_title'],
                    'account_number' => $data['account_number'],
                    'hourly_rate' => $data['rate_amount'] ?? 0,
                    'rate_type' => $data['rate_type'] ?? Worker::RATE_TYPE_HOUR,
                ]
            );
        }

        return redirect()->route('backups.index')->with('status', 'Workers imported successfully.');
    }
}
