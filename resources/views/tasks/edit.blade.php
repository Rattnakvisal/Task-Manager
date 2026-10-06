@extends('layouts.app')

@section('title', 'Edit Task - WorkMind')

@section('content')
<div class="mx-auto max-w-xl space-y-4 pb-12">
    {{-- Top Bar Navigation --}}
    <nav class="flex items-center justify-between gap-3">
        <a href="{{ route('tasks.show', $task) }}"
            class="group inline-flex items-center gap-1.5 rounded-xl border border-slate-200/90 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 shadow-2xs transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800"
            title="Back to task details">
            <x-icon name="arrow-left" class="h-3.5 w-3.5 transition group-hover:-translate-x-0.5" />
            <span>Back to Task</span>
        </a>

        <span class="text-xs font-semibold text-slate-400 dark:text-slate-500 font-mono">#{{ $task->id }}</span>
    </nav>

    {{-- Main Small Clean Form Card --}}
    <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-[0_2px_12px_-4px_rgba(0,0,0,0.04)] sm:p-7 dark:border-slate-800 dark:bg-slate-900">
        <div class="mb-5 flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800">
            <div>
                <h1 class="text-xl font-black tracking-tight text-slate-950 dark:text-white" data-i18n="edit_task">
                    Edit Task
                </h1>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                    Update task parameters, milestones, and details.
                </p>
            </div>
            <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 shadow-xs dark:bg-blue-950/50 dark:text-blue-400">
                <x-icon name="edit" class="h-5 w-5" />
            </span>
        </div>

        <form method="POST" action="{{ route('tasks.update', $task) }}" data-edit-task-form novalidate autocomplete="off" class="space-y-5">
            @csrf
            @method('PUT')
            @include('tasks._form', ['task' => $task])

            <div class="flex items-center justify-end gap-2.5 border-t border-slate-100 pt-4 dark:border-slate-800">
                <a href="{{ route('tasks.show', $task) }}"
                    class="inline-flex h-9 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-xs font-bold text-slate-600 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-750"
                    data-i18n="cancel">
                    Cancel
                </a>
                <button type="submit"
                    class="inline-flex h-9 items-center justify-center gap-1.5 rounded-xl bg-blue-600 px-5 text-xs font-bold text-white shadow-xs shadow-blue-500/20 transition hover:bg-blue-700 active:scale-95">
                    <x-icon name="check" class="h-3.5 w-3.5" />
                    <span data-i18n="save_changes">Save Changes</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
