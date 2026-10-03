<?php

declare(strict_types=1);

// Laravel cannot report failures that happen before its exception handler is
// ready, so send early bootstrap errors to Vercel Runtime Logs.
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', 'php://stderr');

register_shutdown_function(static function (): void {
    $error = error_get_last();

    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        error_log(sprintf(
            '[vercel-bootstrap] %s in %s:%d',
            $error['message'],
            $error['file'],
            $error['line'],
        ));
    }
});

// Blade's compiled-view directory must exist on Vercel's writable /tmp disk.
$compiledViewPath = getenv('VIEW_COMPILED_PATH');

if (is_string($compiledViewPath) && $compiledViewPath !== '' && ! is_dir($compiledViewPath)) {
    if (! mkdir($compiledViewPath, 0755, true) && ! is_dir($compiledViewPath)) {
        throw new RuntimeException("Unable to create compiled view directory: {$compiledViewPath}");
    }
}

try {
    require __DIR__.'/../public/index.php';
} catch (Throwable $exception) {
    error_log(sprintf(
        '[vercel-bootstrap] %s: %s in %s:%d%s%s',
        $exception::class,
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        PHP_EOL,
        $exception->getTraceAsString(),
    ));

    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Application failed to start. Check the Vercel Runtime Logs.';
}
