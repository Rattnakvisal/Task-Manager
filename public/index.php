<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

// A zero-configuration Vercel demo starts with a fresh SQLite file in /tmp.
// Initialize its schema once per cold function instance. Production projects
// should use PostgreSQL and run migrations as part of their release process.
if (getenv('VERCEL_RUNTIME_MIGRATE') === 'true') {
    $hasPgsql = (bool) (getenv('DATABASE_URL') || (getenv('DB_URL') && str_starts_with((string) getenv('DB_URL'), 'postgres')) || getenv('DB_CONNECTION') === 'pgsql');
    $database = getenv('DB_DATABASE');

    if (! $hasPgsql && is_string($database) && $database !== '' && ! file_exists($database)) {
        $dir = dirname($database);
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @touch($database);
    }

    try {
        $kernel = $app->make(Kernel::class);
        $kernel->bootstrap();
        Artisan::call('migrate', ['--force' => true]);
    } catch (Throwable $e) {
        error_log('[vercel-migrate] Migration notice: '.$e->getMessage());
    }
    putenv('VERCEL_RUNTIME_MIGRATE=false');
}

$app->handleRequest(Request::capture());
