<?php

use App\Http\Controllers\Api\MobileAuthController;
use App\Http\Controllers\Api\TaskApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile')->group(function () {
    Route::get('/', fn (Illuminate\Http\Request $request) => response()->json([
        'status' => 'ok',
        'app' => config('app.name'),
        'message' => 'Task Manager Mobile API is running.',
        'client_ip' => $request->ip(),
        'server_host' => $request->getHost(),
        'timestamp' => now()->toIso8601String(),
    ]));
    Route::get('/ping', fn (Illuminate\Http\Request $request) => response()->json([
        'status' => 'ok',
        'app' => config('app.name'),
        'client_ip' => $request->ip(),
        'timestamp' => now()->toIso8601String(),
    ]));

    Route::post('/login', [MobileAuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('/register', [MobileAuthController::class, 'register'])->middleware('throttle:5,1');

    Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {
        Route::get('/me', [MobileAuthController::class, 'me']);
        Route::post('/logout', [MobileAuthController::class, 'logout']);
        Route::get('/dashboard', [TaskApiController::class, 'dashboard']);
        Route::get('/calendar', [TaskApiController::class, 'calendar']);
        Route::get('/notifications', [TaskApiController::class, 'notifications']);
        Route::apiResource('tasks', TaskApiController::class)->names([
            'index' => 'mobile.tasks.index',
            'store' => 'mobile.tasks.store',
            'show' => 'mobile.tasks.show',
            'update' => 'mobile.tasks.update',
            'destroy' => 'mobile.tasks.destroy',
        ]);
    });
});
