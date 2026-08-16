<?php

declare(strict_types=1);

// Vercel Functions have a read-only project filesystem. Laravel's generated
// views, logs, and temporary framework files must live in the writable /tmp.
$storagePath = '/tmp/task-manager-storage';

foreach (['framework/cache', 'framework/sessions', 'framework/views', 'logs'] as $directory) {
    $path = $storagePath.'/'.$directory;

    if (! is_dir($path)) {
        mkdir($path, 0755, true);
    }
}

putenv("LARAVEL_STORAGE_PATH={$storagePath}");
$_ENV['LARAVEL_STORAGE_PATH'] = $storagePath;
$_SERVER['LARAVEL_STORAGE_PATH'] = $storagePath;

// A Vercel Function cannot write to the SQLite file shipped with the
// deployment. Keep PostgreSQL (or another explicitly configured database)
// untouched, but make the zero-configuration SQLite demo writable by copying
// it to /tmp once per warm function instance.
$databaseUrl = getenv('DB_URL');
$vercelPostgresUrl = getenv('POSTGRES_URL');
$databaseConnection = getenv('DB_CONNECTION');
$hasRemoteDatabase = $databaseUrl !== false
    || $vercelPostgresUrl !== false
    || ($databaseConnection !== false && $databaseConnection !== 'sqlite');

// Vercel Postgres and Neon integrations commonly expose POSTGRES_URL. Laravel
// reads DB_URL, so bridge the platform variable without requiring duplicate
// secrets in every Vercel environment.
if ($databaseUrl === false && $vercelPostgresUrl !== false) {
    putenv("DB_URL={$vercelPostgresUrl}");
    $_ENV['DB_URL'] = $_SERVER['DB_URL'] = $vercelPostgresUrl;

    if ($databaseConnection === false || $databaseConnection === 'sqlite') {
        putenv('DB_CONNECTION=pgsql');
        $_ENV['DB_CONNECTION'] = $_SERVER['DB_CONNECTION'] = 'pgsql';
    }
}

if (! $hasRemoteDatabase) {
    $sourceDatabase = __DIR__.'/../database/database.sqlite';
    $runtimeDatabase = '/tmp/task-manager.sqlite';

    if (! is_file($runtimeDatabase) && is_file($sourceDatabase)) {
        copy($sourceDatabase, $runtimeDatabase);
    }

    putenv("DB_CONNECTION=sqlite");
    putenv("DB_DATABASE={$runtimeDatabase}");
    putenv('SESSION_DRIVER=file');
    putenv('CACHE_STORE=file');
    putenv('QUEUE_CONNECTION=sync');

    $_ENV['DB_CONNECTION'] = $_SERVER['DB_CONNECTION'] = 'sqlite';
    $_ENV['DB_DATABASE'] = $_SERVER['DB_DATABASE'] = $runtimeDatabase;
    $_ENV['SESSION_DRIVER'] = $_SERVER['SESSION_DRIVER'] = 'file';
    $_ENV['CACHE_STORE'] = $_SERVER['CACHE_STORE'] = 'file';
    $_ENV['QUEUE_CONNECTION'] = $_SERVER['QUEUE_CONNECTION'] = 'sync';
}

// Vercel captures stderr in Runtime Logs. Use it by default so production
// failures remain diagnosable without exposing exception details to visitors.
if (getenv('LOG_CHANNEL') === false) {
    putenv('LOG_CHANNEL=stderr');
    $_ENV['LOG_CHANNEL'] = 'stderr';
    $_SERVER['LOG_CHANNEL'] = 'stderr';
}

require __DIR__.'/../public/index.php';
