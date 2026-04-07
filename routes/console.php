<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('scheduler:backup-database', function () {
    $databasePath = config('database.connections.sqlite.database');

    if (! $databasePath || ! File::exists($databasePath)) {
        $this->error('SQLite database file was not found.');

        return self::FAILURE;
    }

    $dateFolder = now()->format('d-m-Y');
    $backupDirectory = storage_path('app/backups/'.$dateFolder);
    $backupFile = $backupDirectory.'/database-'.$dateFolder.'.sqlite';

    File::ensureDirectoryExists($backupDirectory);
    File::copy($databasePath, $backupFile);

    $this->info('Database backup created at '.$backupFile);

    return self::SUCCESS;
})->purpose('Create a dated backup of the SQLite database');

Schedule::command('scheduler:backup-database')->daily();
