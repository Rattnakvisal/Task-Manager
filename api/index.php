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

require __DIR__.'/../public/index.php';
