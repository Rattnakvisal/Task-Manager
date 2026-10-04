@extends('layouts.app')

@section('title', $title . ' Tasks - Task Manager')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-blue-600 dark:text-blue-400">Task workspace</p>
                    <h1 class="mt-2 text-3xl font-bold text-slate-950 dark:text-white">{{ $title }} Tasks</h1>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ $subtitle }}</p>
                    <p class="mt-3 text-sm font-semibold text-slate-600 dark:text-slate-300">{{ $total }} {{ Str::plural('task', $total) }}</p>
                </div>
                <a href="{{ route('tasks.create') }}" data-open-task-modal class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white hover:bg-blue-700">
                    <x-icon name="plus" class="h-4 w-4" /> <span data-i18n="create_task">Create Task</span>
                </a>
            </div>
        </section>

        <form method="GET" action="{{ route('tasks.' . $section) }}" class="flex flex-wrap items-end gap-3">
            <label class="min-w-0 flex-1 text-sm font-semibold text-slate-600 dark:text-slate-300">
                Search tasks
                <input type="search" name="q" value="{{ request('q') }}" maxlength="255" placeholder="Title, description, or category" class="mt-2 block w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-white">
            </label>
            <label class="text-sm font-semibold text-slate-600 dark:text-slate-300">
                Priority
                <select name="priority" class="mt-2 block rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                    <option value="">All priorities</option>
                    @foreach (['low', 'medium', 'high'] as $priority)
                        <option value="{{ $priority }}" @selected(request('priority') === $priority)>{{ ucfirst($priority) }}</option>
                    @endforeach
                </select>
            </label>
            <button type="submit" class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white hover:bg-blue-700">Filter</button>
            @if (request()->filled('q') || request()->filled('priority'))
                <a href="{{ route('tasks.' . $section) }}" class="px-2 py-3 text-sm font-semibold text-blue-600 dark:text-blue-400">Clear filters</a>
            @endif
        </form>

        <section aria-label="{{ $title }} tasks" class="overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
            @forelse ($tasks as $task)
                <article class="flex flex-wrap items-center gap-4 border-b border-slate-100 p-5 last:border-0 dark:border-slate-800">
                    <form method="POST" action="{{ route('tasks.toggle-status', $task) }}">
                        @csrf @method('PATCH')
                        <button type="submit" aria-label="{{ $task->status === 'completed' ? 'Reopen' : 'Complete' }} {{ $task->title }}" class="flex h-10 w-10 items-center justify-center rounded-xl {{ $task->status === 'completed' ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300' }}">
                            <x-icon name="check-circle" />
                        </button>
                    </form>
                    <div class="min-w-0 flex-1">
                        <a href="{{ route('tasks.show', $task) }}" class="break-words text-base font-bold text-slate-900 hover:text-blue-600 dark:text-white dark:hover:text-blue-400">{{ $task->title }}</a>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $task->category ?: 'General' }} · {{ ucfirst(str_replace('_', ' ', $task->status)) }} · {{ ucfirst($task->priority) }} priority</p>
                        <p class="mt-1 text-xs {{ $task->is_overdue ? 'text-rose-500' : 'text-slate-500 dark:text-slate-400' }}">{{ $task->due_date ? 'Due ' . $task->due_date->format('M j, Y') : 'No due date' }}@if($task->is_pinned) · Pinned @endif</p>
                    </div>
                    <a href="{{ route('tasks.edit', $task) }}" data-edit-task-id="{{ $task->id }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-600 hover:text-blue-600 dark:border-slate-700 dark:text-slate-300"><x-icon name="edit" class="h-4 w-4" /> Edit</a>
                </article>
            @empty
                <div class="px-6 py-16 text-center">
                    <x-icon name="check-circle" class="mx-auto h-10 w-10 text-blue-500" />
                    <h2 class="mt-4 text-lg font-bold text-slate-900 dark:text-white">{{ $total > 0 ? 'No matching tasks' : 'No ' . strtolower($title) . ' tasks' }}</h2>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ $total > 0 ? 'Try another search or clear your filters.' : match ($section) { 'today' => 'Tasks with today’s due date will appear here.', 'overdue' => 'You are all caught up. Keep your next deadlines on track.', 'completed' => 'Tasks you finish will appear here.' } }}</p>
                    <a href="{{ route($total > 0 ? 'tasks.' . $section : 'tasks.index') }}" class="mt-5 inline-block text-sm font-bold text-blue-600 dark:text-blue-400">{{ $total > 0 ? 'Clear filters' : 'Back to My Tasks' }} →</a>
                </div>
            @endforelse
        </section>

        {{ $tasks->links() }}
    </div>
@endsection
