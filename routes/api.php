<?php

use App\Http\Controllers\Api\TaskApiController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [TaskApiController::class, 'dashboard']);
Route::get('/calendar', [TaskApiController::class, 'calendar']);
Route::get('/priority', [TaskApiController::class, 'priority']);
Route::get('/all-tasks', [TaskApiController::class, 'allTasks']);
Route::get('/completed', [TaskApiController::class, 'completed']);
Route::get('/search', [TaskApiController::class, 'search']);
Route::get('/notifications', [TaskApiController::class, 'notifications']);

Route::apiResource('tasks', TaskApiController::class)->names([
    'index' => 'api.tasks.index',
    'store' => 'api.tasks.store',
    'show' => 'api.tasks.show',
    'update' => 'api.tasks.update',
    'destroy' => 'api.tasks.destroy',
]);
