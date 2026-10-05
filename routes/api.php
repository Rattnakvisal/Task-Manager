<?php

use App\Http\Controllers\Api\TaskApiController;
use App\Http\Controllers\TaskAlertController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'auth.session'])->group(function () {
    Route::get('/dashboard', [TaskApiController::class, 'dashboard']);
    Route::get('/calendar', [TaskApiController::class, 'calendar']);
    Route::get('/priority', [TaskApiController::class, 'priority']);
    Route::get('/all-tasks', [TaskApiController::class, 'allTasks']);
    Route::get('/completed', [TaskApiController::class, 'completed']);
    Route::get('/search', [TaskApiController::class, 'search']);
    Route::get('/notifications', [TaskApiController::class, 'notifications']);
    Route::get('/task-alerts', [TaskAlertController::class, 'index'])->name('api.task-alerts.index');
    Route::patch('/task-alerts/read-all', [TaskAlertController::class, 'markAllRead'])->name('api.task-alerts.read-all');
    Route::patch('/task-alerts/{notification}/read', [TaskAlertController::class, 'markRead'])->name('api.task-alerts.read');

    Route::apiResource('tasks', TaskApiController::class)->names([
        'index' => 'api.tasks.index',
        'store' => 'api.tasks.store',
        'show' => 'api.tasks.show',
        'update' => 'api.tasks.update',
        'destroy' => 'api.tasks.destroy',
    ]);
});
