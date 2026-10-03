@php
    $priorityColors = [
        'low' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/10 dark:bg-emerald-950/40 dark:text-emerald-300',
        'medium' => 'bg-amber-50 text-amber-700 ring-amber-600/10 dark:bg-amber-950/40 dark:text-amber-300',
        'high' => 'bg-rose-50 text-rose-700 ring-rose-600/10 dark:bg-rose-950/40 dark:text-rose-300',
    ];

    $priorityDots = [
        'low' => 'bg-emerald-500',
        'medium' => 'bg-amber-500',
        'high' => 'bg-rose-500',
    ];

    $categoryColors = [
        'Work' => 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/50 dark:text-indigo-300 dark:border-indigo-800',
        'Personal' => 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/50 dark:text-purple-300 dark:border-purple-800',
        'Urgent' => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/50 dark:text-rose-300 dark:border-rose-800',
        'Design' => 'bg-pink-50 text-pink-700 border-pink-200 dark:bg-pink-950/50 dark:text-pink-300 dark:border-pink-800',
        'Dev' => 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-950/50 dark:text-sky-300 dark:border-sky-800',
        'Study' => 'bg-teal-50 text-teal-700 border-teal-200 dark:bg-teal-950/50 dark:text-teal-300 dark:border-teal-800',
        'Finance' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-800',
    ];
@endphp

<div
    class="task-card group relative rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm transition-all hover:border-slate-300 hover:shadow-md dark:border-slate-800 dark:bg-slate-900 {{ $task->status === 'completed' ? 'bg-slate-50/50 opacity-90 dark:bg-slate-900/60' : '' }}"
    draggable="true"
    data-task-id="{{ $task->id }}"
    data-task-status="{{ $task->status }}"
    id="task-card-{{ $task->id }}"
>
    {{-- Card Header: Badges & Pin --}}
    <div class="mb-2.5 flex items-center justify-between gap-2">
        <div class="flex flex-wrap items-center gap-1.5">
            {{-- Category badge --}}
            @if ($task->category)
                <span class="rounded-full border px-2 py-0.5 text-[10px] font-bold tracking-wide {{ $categoryColors[$task->category] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                    {{ $task->category }}
                </span>
            @endif

            {{-- Priority pill --}}
            <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider {{ $priorityColors[$task->priority] ?? 'bg-slate-100 text-slate-600' }}">
                <span class="h-1.5 w-1.5 rounded-full {{ $priorityDots[$task->priority] ?? 'bg-slate-400' }}"></span>
                {{ $task->priority }}
            </span>
        </div>

        <div class="flex items-center gap-1">
            {{-- Pin toggle button --}}
            <button
                type="button"
                data-toggle-pin-id="{{ $task->id }}"
                class="rounded-lg p-1 transition {{ $task->is_pinned ? 'text-amber-500' : 'text-slate-300 hover:text-amber-500 dark:text-slate-600' }}"
                title="{{ $task->is_pinned ? 'Unpin' : 'Pin to top' }}"
            >
                <x-icon name="pin" class="h-3.5 w-3.5" />
            </button>

            {{-- 1-Click Complete Checkbox --}}
            <button
                type="button"
                data-toggle-complete-id="{{ $task->id }}"
                class="flex h-6 w-6 items-center justify-center rounded-lg border transition {{ $task->status === 'completed' ? 'border-emerald-500 bg-emerald-500 text-white shadow-sm' : 'border-slate-300 bg-white hover:border-emerald-500 dark:border-slate-700 dark:bg-slate-800' }}"
                title="{{ $task->status === 'completed' ? 'Mark as incomplete' : 'Mark as complete' }}"
            >
                @if ($task->status === 'completed')
                    <x-icon name="check" class="h-3.5 w-3.5" />
                @endif
            </button>
        </div>
    </div>

    {{-- Task Title --}}
    <h4
        class="text-sm font-bold text-slate-950 transition dark:text-white {{ $task->status === 'completed' ? 'task-completed-text' : '' }}"
        id="card-title-{{ $task->id }}"
    >
        {{ $task->title }}
    </h4>

    {{-- Description --}}
    @if ($task->description)
        <p class="mt-1 line-clamp-2 text-xs leading-5 text-slate-500 dark:text-slate-400">
            {{ $task->description }}
        </p>
    @endif

    {{-- Subtasks / Checklist preview --}}
    @if ($task->subtasks_count > 0)
        <div class="mt-3 rounded-xl border border-slate-100 bg-slate-50/80 p-2.5 dark:border-slate-800 dark:bg-slate-800/40">
            <div class="flex items-center justify-between text-[11px] font-bold text-slate-600 dark:text-slate-300">
                <span class="flex items-center gap-1.5">
                    <x-icon name="check-circle" class="h-3 w-3 text-emerald-500" />
                    <span>Checklist ({{ $task->completed_subtasks_count }}/{{ $task->subtasks_count }})</span>
                </span>
                <span class="text-slate-400">{{ $task->subtasks_progress }}%</span>
            </div>

            <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                <div class="h-full bg-emerald-500 transition-all duration-300" style="width: {{ $task->subtasks_progress }}%"></div>
            </div>

            {{-- Subtasks items list --}}
            <div class="mt-2 space-y-1.5">
                @foreach ($task->subtasks as $st)
                    <label class="flex items-center gap-2 cursor-pointer text-[11px] text-slate-600 dark:text-slate-400">
                        <input
                            type="checkbox"
                            data-task-subtask-toggle="{{ $task->id }}"
                            data-subtask-id="{{ $st['id'] ?? '' }}"
                            @checked(!empty($st['completed']))
                            class="h-3.5 w-3.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 dark:border-slate-700"
                        >
                        <span class="{{ !empty($st['completed']) ? 'line-through text-slate-400' : '' }}">
                            {{ $st['title'] ?? '' }}
                        </span>
                    </label>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Card Footer: Date & Actions --}}
    <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-2.5 text-xs text-slate-400 dark:border-slate-800">
        <div>
            @if ($task->due_date)
                <span class="inline-flex items-center gap-1 text-[11px] font-semibold {{ $task->is_overdue ? 'text-rose-600 dark:text-rose-400' : 'text-slate-500 dark:text-slate-400' }}">
                    <x-icon name="calendar" class="h-3 w-3" />
                    <span>{{ $task->due_date->format('M d') }}</span>
                    @if ($task->is_overdue)
                        <span class="rounded bg-rose-100 px-1 text-[9px] font-bold text-rose-700 dark:bg-rose-950 dark:text-rose-300">Overdue</span>
                    @endif
                </span>
            @endif
        </div>

        <div class="flex items-center gap-1 opacity-80 group-hover:opacity-100">
            <button
                type="button"
                data-edit-task-id="{{ $task->id }}"
                class="rounded-lg p-1.5 text-slate-400 transition hover:bg-blue-50 hover:text-blue-600 dark:hover:bg-slate-800 dark:hover:text-blue-400"
                title="Edit Task"
            >
                <x-icon name="edit" class="h-3.5 w-3.5" />
            </button>

            <form method="POST" action="{{ route('tasks.destroy', $task) }}" onsubmit="return confirm('Delete this task?');" class="inline">
                @csrf
                @method('DELETE')
                <button
                    type="submit"
                    class="rounded-lg p-1.5 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-slate-800 dark:hover:text-rose-400"
                    title="Delete Task"
                >
                    <x-icon name="trash" class="h-3.5 w-3.5" />
                </button>
            </form>
        </div>
    </div>
</div>
