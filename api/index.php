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

// Fallback: If a request for an existing public asset reaches PHP, serve it directly.
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
if ($requestPath !== '' && $requestPath !== '/' && $requestPath !== '/index.php') {
    $publicDir = realpath(__DIR__.'/../public');
    $assetPath = realpath(__DIR__.'/../public'.$requestPath);

    if ($publicDir !== false && $assetPath !== false && str_starts_with($assetPath, $publicDir) && is_file($assetPath)) {
        $ext = strtolower(pathinfo($assetPath, PATHINFO_EXTENSION));
        $contentTypes = [
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            'ico' => 'image/x-icon',
            'json' => 'application/json',
            'css' => 'text/css; charset=UTF-8',
            'js' => 'application/javascript; charset=UTF-8',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'txt' => 'text/plain; charset=UTF-8',
        ];

        $contentType = $contentTypes[$ext] ?? (mime_content_type($assetPath) ?: 'application/octet-stream');

        header('Content-Type: '.$contentType);
        header('Content-Length: '.(string) filesize($assetPath));
        header('Cache-Control: public, max-age=31536000, immutable');
        readfile($assetPath);
        exit;
    }
}

try {
    require __DIR__.'/../public/index.php';
} catch (Throwable $exception) {
    $errorChain = [];
    $current = $exception;

    do {
        $errorChain[] = sprintf(
            '%s: %s in %s:%d',
            $current::class,
            $current->getMessage(),
            $current->getFile(),
            $current->getLine(),
        );
    } while (($current = $current->getPrevious()) && count($errorChain) < 4);

    error_log('[vercel-bootstrap] '.implode(' <- ', $errorChain));

    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Application failed to start. Check the Vercel Runtime Logs.';
}
