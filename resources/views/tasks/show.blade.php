@extends('layouts.app')

@section('title', $task->title . ' - Task Manager')

@section('content')
    <div class="mx-auto max-w-4xl space-y-6">
        <a href="{{ route('tasks.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-blue-600 dark:text-blue-400"><x-icon name="list" class="h-4 w-4" /> Back to tasks</a>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-semibold uppercase tracking-widest text-blue-600 dark:text-blue-400">Task Details</p>
                    <h1 class="mt-3 break-words text-3xl font-bold text-slate-950 dark:text-white">{{ $task->title }}</h1>
                </div>
                <a href="{{ route('tasks.edit', $task) }}" data-edit-task-id="{{ $task->id }}" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white hover:bg-blue-700"><x-icon name="edit" class="h-4 w-4" /> Edit Task</a>
            </div>

            <dl class="mt-6 grid gap-5 rounded-xl bg-slate-50 p-5 text-sm sm:grid-cols-3 dark:bg-slate-800/60">
                @foreach ([
                    'Status' => ucfirst(str_replace('_', ' ', $task->status)),
                    'Priority' => ucfirst($task->priority),
                    'Category' => $task->category ?: 'General',
                    'Due date' => $task->due_date?->format('M j, Y') ?? 'No due date',
                    'End date' => $task->end_date?->format('M j, Y') ?? 'No end date',
                    'Updated' => $task->updated_at->diffForHumans(),
                ] as $label => $value)
                    <div><dt class="text-slate-500 dark:text-slate-400">{{ $label }}</dt><dd class="mt-1 break-words font-semibold text-slate-900 dark:text-white">{{ $value }}</dd></div>
                @endforeach
            </dl>
            @if($task->is_overdue)
                <p class="mt-4 text-sm font-semibold text-rose-500">This task is overdue.</p>
            @endif

            <h2 class="mt-8 text-lg font-bold text-slate-900 dark:text-white">Description</h2>
            <p class="mt-3 whitespace-pre-wrap break-words text-sm leading-7 text-slate-600 dark:text-slate-300">{{ $task->description ?: 'No description added.' }}</p>

            @if($task->tags)
                <div class="mt-5 flex flex-wrap gap-2" aria-label="Tags">
                    @foreach($task->tags as $tag)
                        <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-600 dark:bg-blue-950/60 dark:text-blue-300">{{ $tag }}</span>
                    @endforeach
                </div>
            @endif

            <div class="mt-8 flex flex-wrap gap-3 border-t border-slate-200 pt-5 dark:border-slate-800">
                <form method="POST" action="{{ route('tasks.toggle-status', $task) }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="rounded-xl bg-emerald-600 px-4 py-3 text-sm font-bold text-white hover:bg-emerald-700">{{ $task->status === 'completed' ? 'Reopen Task' : 'Mark Complete' }}</button>
                </form>
                <form method="POST" action="{{ route('tasks.toggle-pin', $task) }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-600 dark:border-slate-700 dark:text-slate-300">{{ $task->is_pinned ? 'Unpin Task' : 'Pin Task' }}</button>
                </form>
                <form method="POST" action="{{ route('tasks.destroy', $task) }}" data-confirm="Delete this task?">
                    @csrf @method('DELETE')
                    <button type="submit" class="rounded-xl px-4 py-3 text-sm font-semibold text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40">Delete Task</button>
                </form>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">Checklist <span class="ml-2 text-sm font-medium text-slate-500 dark:text-slate-400">{{ $task->completed_subtasks_count }}/{{ $task->subtasks_count }}</span></h2>
            @forelse($task->subtasks ?? [] as $subtask)
                <form method="POST" action="{{ route('tasks.toggle-subtask', [$task, $subtask['id']]) }}" class="mt-4">
                    @csrf @method('PATCH')
                    <button type="submit" class="flex w-full items-center gap-3 text-left text-sm {{ !empty($subtask['completed']) ? 'text-emerald-600' : 'text-slate-600 dark:text-slate-300' }}">
                        <x-icon :name="!empty($subtask['completed']) ? 'check-circle' : 'circle'" class="h-5 w-5 shrink-0" />
                        <span class="break-words {{ !empty($subtask['completed']) ? 'line-through' : '' }}">{{ $subtask['title'] }}</span>
                    </button>
                </form>
            @empty
                <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">No checklist items added.</p>
            @endforelse
        </section>
    </div>
@endsection
