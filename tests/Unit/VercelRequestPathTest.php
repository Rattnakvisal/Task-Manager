<?php

use Illuminate\Http\Request;

it('preserves API and web paths through the Vercel front controller', function (string $uri, string $path) {
    $original = $_SERVER;

    try {
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => $uri,
            'SCRIPT_FILENAME' => dirname(__DIR__, 2).'/api/index.php',
            'SCRIPT_NAME' => '/api/index.php',
            'PHP_SELF' => '/api/index.php',
            'HTTP_HOST' => 'taskflow-ai-kh.vercel.app',
            'HTTPS' => 'on',
        ];

        require dirname(__DIR__, 2).'/bootstrap/vercel-request.php';
        $request = Request::capture();

        expect($request->getBaseUrl())->toBe('')
            ->and($request->getPathInfo())->toBe($path)
            ->and($request->getRequestUri())->toBe($uri);
    } finally {
        $_SERVER = $original;
    }
})->with([
    ['/api/mobile/ping', '/api/mobile/ping'],
    ['/api/mobile/login', '/api/mobile/login'],
    ['/api/mobile/tasks?q=hello', '/api/mobile/tasks'],
    ['/api/tasks', '/api/tasks'],
    ['/sign-in', '/sign-in'],
    ['/dashboard', '/dashboard'],
]);
