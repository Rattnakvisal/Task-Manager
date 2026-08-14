<?php

use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::get('/', [TaskController::class, 'dashboard'])->name('home');
Route::get('/dashboard', [TaskController::class, 'dashboard'])->name('dashboard');
Route::get('/calendar', [TaskController::class, 'calendar'])->name('calendar');
Route::get('/priority', [TaskController::class, 'priority'])->name('priority');
Route::get('/all-tasks', [TaskController::class, 'allTasks'])->name('all-tasks');
Route::get('/completed', [TaskController::class, 'completed'])->name('completed');

Route::resource('tasks', TaskController::class);
