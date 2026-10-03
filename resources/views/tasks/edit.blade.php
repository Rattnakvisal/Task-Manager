@extends('layouts.app')

@section('title', 'Edit Task')

@section('content')
    <div class="mx-auto max-w-3xl">
        <div class="mb-6">
            <a href="{{ route('tasks.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-blue-600 transition hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                <x-icon name="list" class="h-4 w-4" /> <span data-i18n="my_tasks">Back to tasks</span>
            </a>
            <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-950 dark:text-white" data-i18n="edit_task">Edit Task</h1>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400" data-i18n="edit_task_sub">Update this task's details and progress.</p>
        </div>
        <form method="POST" action="{{ route('tasks.update', $task) }}" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 transition-colors duration-200" autocomplete="off">
            @csrf
            @method('PUT')
            @include('tasks._form', ['task' => $task])
            <div class="flex justify-end gap-3 border-t border-slate-200 pt-5 dark:border-slate-800">
                <a href="{{ route('tasks.index') }}" class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700" data-i18n="cancel">Cancel</a>
                <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-blue-950 px-5 text-sm font-semibold text-white shadow-md shadow-blue-950/10 transition hover:bg-blue-900 dark:bg-blue-600 dark:hover:bg-blue-500">
                    <x-icon name="check" class="h-4 w-4" /> <span data-i18n="save_changes">Save changes</span>
                </button>
            </div>
        </form>
    </div>
@endsection
