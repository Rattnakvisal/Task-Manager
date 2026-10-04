@extends('layouts.app')

@section('title', 'Edit Task - Task Manager')

@section('content')
    <div class="mx-auto max-w-3xl">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <a href="{{ route('tasks.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-blue-600 transition hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                    <x-icon name="list" class="h-4 w-4" />
                    <span data-i18n="my_tasks">Back to tasks</span>
                </a>
                <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-950 dark:text-white" data-i18n="edit_task">Edit Task</h1>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400" data-i18n="edit_task_sub">Update this task's details and progress.</p>
            </div>

            <button type="button" data-edit-task-id="{{ $task->id }}" class="inline-flex items-center gap-2 rounded-xl border border-blue-200 bg-blue-50/80 px-4 py-2.5 text-xs font-bold text-blue-700 shadow-sm transition hover:bg-blue-100 hover:border-blue-300 dark:border-blue-800/80 dark:bg-blue-950/40 dark:text-blue-300">
                <x-icon name="edit" class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                <span data-i18n="edit_task">Open Popup Form</span>
            </button>
        </div>

        <form method="POST" action="{{ route('tasks.update', $task) }}" data-edit-task-form class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-5 dark:border-slate-800 dark:bg-slate-900 transition-colors duration-200" autocomplete="off">
            @csrf
            @method('PUT')
            @include('tasks._form', ['task' => $task])

            <div class="flex justify-end gap-3 border-t border-slate-200 pt-5 dark:border-slate-800">
                <a href="{{ route('tasks.index') }}" class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700" data-i18n="cancel">
                    Cancel
                </a>
                <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 text-sm font-semibold text-white shadow-md shadow-blue-600/20 transition hover:bg-blue-700 active:scale-95 dark:bg-blue-600 dark:hover:bg-blue-500">
                    <x-icon name="check" class="h-4 w-4" />
                    <span data-i18n="save_changes">Save changes</span>
                </button>
            </div>
        </form>
    </div>
@endsection
