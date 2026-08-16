<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

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
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    putenv('VERCEL_RUNTIME_MIGRATE=false');
}

$app->handleRequest(Request::capture());
