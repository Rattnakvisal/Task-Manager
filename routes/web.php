<?php

use App\Http\Controllers\AiCopilotController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/sign-in', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/sign-in', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::get('/sign-up', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/sign-up', [RegisteredUserController::class, 'store'])->name('register.store');
});

Route::middleware(['auth', 'auth.session'])->group(function () {
    Route::redirect('/', '/dashboard')->name('home');
    Route::get('/dashboard', [TaskController::class, 'dashboard'])->name('dashboard');
    Route::get('/projects', [TaskController::class, 'projects'])->name('projects');
    Route::get('/calendar', [TaskController::class, 'calendar'])->name('calendar');
    Route::get('/priority', [TaskController::class, 'priority'])->name('priority');
    Route::get('/analytics', [TaskController::class, 'analytics'])->name('analytics');
    Route::get('/all-tasks', [TaskController::class, 'allTasks'])->name('all-tasks');
    Route::get('/tasks/today', [TaskController::class, 'today'])->name('tasks.today');
    Route::get('/tasks/overdue', [TaskController::class, 'overdue'])->name('tasks.overdue');
    Route::get('/tasks/completed', [TaskController::class, 'completed'])->name('tasks.completed');
    Route::post('/sign-out', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/tasks/quick', [TaskController::class, 'quickStore'])->name('tasks.quick-store');
    Route::patch('/tasks/{task}/toggle-status', [TaskController::class, 'toggleStatus'])->name('tasks.toggle-status');
    Route::patch('/tasks/{task}/toggle-pin', [TaskController::class, 'togglePin'])->name('tasks.toggle-pin');
    Route::patch('/tasks/{task}/subtasks/{subtaskId}/toggle', [TaskController::class, 'toggleSubtask'])->name('tasks.toggle-subtask');
    Route::get('/tasks-export/{format}', [TaskController::class, 'export'])->name('tasks.export');

    // AI Task Copilot Routes (Rate limited for abuse and cost protection)
    Route::middleware('throttle:60,1')->group(function () {
        Route::post('/tasks/ai/breakdown', [AiCopilotController::class, 'breakdown'])->name('tasks.ai.breakdown');
        Route::post('/tasks/{task}/ai-breakdown', [AiCopilotController::class, 'breakdownExistingTask'])->name('tasks.ai.breakdown-existing');
        Route::post('/tasks/ai/enhance', [AiCopilotController::class, 'enhance'])->name('tasks.ai.enhance');
        Route::post('/tasks/ai/parse-nlp', [AiCopilotController::class, 'parseNlp'])->name('tasks.ai.parse-nlp');
        Route::get('/tasks/ai/standup-brief', [AiCopilotController::class, 'standupBrief'])->name('tasks.ai.standup-brief');
        Route::post('/tasks/ai/chat', [AiCopilotController::class, 'chat'])->name('tasks.ai.chat');
        Route::get('/tasks/ai/chat/history', [AiCopilotController::class, 'chatHistory'])->name('tasks.ai.chat.history');
        Route::post('/tasks/ai/chat/clear', [AiCopilotController::class, 'clearChat'])->name('tasks.ai.chat.clear');
        Route::get('/tasks/ai/preferences', [AiCopilotController::class, 'preferences'])->name('tasks.ai.preferences');
        Route::put('/tasks/ai/preferences', [AiCopilotController::class, 'updatePreferences'])->name('tasks.ai.preferences.update');
        Route::get('/tasks/ai/status', [AiCopilotController::class, 'itStatus'])->name('tasks.ai.status');
    });

    Route::resource('tasks', TaskController::class);
});
