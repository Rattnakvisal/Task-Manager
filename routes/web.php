<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/sign-in', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/sign-in', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::get('/sign-up', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/sign-up', [RegisteredUserController::class, 'store'])->name('register.store');
});

Route::middleware(['auth', 'auth.session'])->group(function () {
    Route::get('/', [TaskController::class, 'dashboard'])->name('home');
    Route::get('/dashboard', [TaskController::class, 'dashboard'])->name('dashboard');
    Route::get('/calendar', [TaskController::class, 'calendar'])->name('calendar');
    Route::get('/priority', [TaskController::class, 'priority'])->name('priority');
    Route::get('/all-tasks', [TaskController::class, 'allTasks'])->name('all-tasks');
    Route::get('/completed', [TaskController::class, 'completed'])->name('completed');
    Route::post('/sign-out', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::post('/tasks/quick', [TaskController::class, 'quickStore'])->name('tasks.quick-store');
    Route::patch('/tasks/{task}/toggle-status', [TaskController::class, 'toggleStatus'])->name('tasks.toggle-status');
    Route::patch('/tasks/{task}/toggle-pin', [TaskController::class, 'togglePin'])->name('tasks.toggle-pin');
    Route::patch('/tasks/{task}/subtasks/{subtaskId}/toggle', [TaskController::class, 'toggleSubtask'])->name('tasks.toggle-subtask');
    Route::get('/tasks-export/{format}', [TaskController::class, 'export'])->name('tasks.export');
    Route::resource('tasks', TaskController::class);
});
