<?php

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Vercel terminates TLS before forwarding the request to PHP. Trust its
        // forwarded protocol/host so Laravel generates HTTPS links and forms.
        $middleware->trustProxies(at: '*');
        $middleware->append(SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

// Safeguard: If external tools (e.g. Vercel CLI) create a minimal .env.local without APP_KEY,
// ensure base variables from .env are safely loaded for local development.
$app->afterLoadingEnvironment(function ($app): void {
    if (empty(env('APP_KEY')) && file_exists($app->environmentPath().'/.env')) {
        \Dotenv\Dotenv::create(
            \Illuminate\Support\Env::getRepository(),
            $app->environmentPath(),
            '.env'
        )->safeLoad();
    }
});

return $app;
