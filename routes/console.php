<?php

use App\Services\BackupService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('scheduler:backup-database', function (BackupService $backups) {
    $snapshot = $backups->createSnapshot('daily-cron');
    $this->info('Backup snapshot created: '.$snapshot);

    $databasePath = config('database.connections.sqlite.database');

    if ($databasePath && File::exists($databasePath)) {
        $dateFolder = now()->format('d-m-Y');
        $backupDirectory = storage_path('app/backups/'.$dateFolder);
        $backupFile = $backupDirectory.'/database-'.$dateFolder.'.sqlite';
        File::ensureDirectoryExists($backupDirectory);
        File::copy($databasePath, $backupFile);
        $this->info('SQLite file copied at '.$backupFile);
    }

    return self::SUCCESS;
})->purpose('Create a daily app backup snapshot and SQLite copy when available');

Schedule::command('scheduler:backup-database')->dailyAt('01:00');
