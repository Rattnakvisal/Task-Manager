@extends('layouts.app')

@section('title', 'Priority Matrix - WorkMind')

@section('content')
    @php
        $columns = [
            'high' => [
                'title' => 'High Priority',
                'description' => 'Critical & urgent items needing attention first.',
                'tone' => 'rose',
                'badge' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/70 dark:text-rose-300 ring-rose-500/20',
                'header_bg' => 'from-rose-50/80 to-rose-100/40 dark:from-rose-950/40 dark:to-rose-900/20',
                'border' => 'border-rose-200/80 dark:border-rose-900/60',
                'bar' => 'bg-rose-500',
                'dot' => 'bg-rose-500',
            ],
            'medium' => [
                'title' => 'Medium Priority',
                'description' => 'Important milestones and scheduled deliverables.',
                'tone' => 'amber',
                'badge' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/70 dark:text-amber-300 ring-amber-500/20',
                'header_bg' => 'from-amber-50/80 to-amber-100/40 dark:from-amber-950/40 dark:to-amber-900/20',
                'border' => 'border-amber-200/80 dark:border-amber-900/60',
                'bar' => 'bg-amber-500',
                'dot' => 'bg-amber-500',
            ],
            'low' => [
                'title' => 'Low Priority',
                'description' => 'Routine tasks and flexible improvements.',
                'tone' => 'emerald',
                'badge' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300 ring-emerald-500/20',
                'header_bg' => 'from-emerald-50/80 to-emerald-100/40 dark:from-emerald-950/40 dark:to-emerald-900/20',
                'border' => 'border-emerald-200/80 dark:border-emerald-900/60',
                'bar' => 'bg-emerald-500',
                'dot' => 'bg-emerald-500',
            ],
        ];

        $highCount = $tasksByPriority['high']->count();
        $mediumCount = $tasksByPriority['medium']->count();
        $lowCount = $tasksByPriority['low']->count();
        $totalPriorityTasks = $highCount + $mediumCount + $lowCount;
    @endphp

    <div class="mx-auto max-w-[1500px] space-y-6">

        {{-- =========================================================
            HERO HEADER
        ========================================================== --}}
        <section class="relative overflow-hidden rounded-3xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900 transition-colors duration-200">
            <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-rose-100/50 blur-3xl dark:bg-rose-900/20"></div>
            <div class="pointer-events-none absolute right-48 top-10 h-36 w-36 rounded-full bg-amber-100/50 blur-3xl dark:bg-amber-900/20"></div>

            <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="mb-3 flex items-center gap-2">
                        <span class="inline-flex items-center gap-2 rounded-full bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-700 dark:bg-rose-950/70 dark:text-rose-400">
                            <span class="h-1.5 w-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                            <span data-i18n="priority">Priority Matrix</span>
                        </span>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                            {{ $totalPriorityTasks }} Total Tasks
                        </span>
                    </div>

                    <h1 class="text-3xl font-extrabold tracking-tight text-slate-950 sm:text-4xl dark:text-white" data-i18n="priority">
                        Priority Board
                    </h1>

                    <p class="mt-2 max-w-xl text-sm leading-6 text-slate-500 sm:text-base dark:text-slate-400" data-i18n="critical_work">
                        Focus your energy where it creates the greatest impact with visual priority tiers.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('tasks.index') }}"
                        class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                        <x-icon name="kanban" class="h-4 w-4" />
                        <span data-i18n="my_tasks">Kanban Board</span>
                    </a>

                    <button type="button" data-open-task-modal
                        class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-blue-950 px-5 text-sm font-semibold text-white shadow-lg shadow-blue-950/10 transition duration-200 hover:-translate-y-0.5 hover:bg-blue-900 dark:bg-blue-600 dark:hover:bg-blue-500">
                        <x-icon name="plus" class="h-4 w-4" />
                        <span data-i18n="new_task">Add Task</span>
                    </button>
                </div>
            </div>

            {{-- Priority Summary Strip --}}
            <div class="mt-6 pt-6 border-t border-slate-100 dark:border-slate-800 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="flex items-center gap-3 rounded-2xl bg-rose-50/60 p-4 dark:bg-rose-950/30 border border-rose-100 dark:border-rose-900/40">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-rose-500 text-white shadow-sm shadow-rose-500/20">
                        <x-icon name="flag" class="h-5 w-5" />
                    </span>
                    <div>
                        <span class="text-xs font-bold text-rose-800 dark:text-rose-300">High Priority</span>
                        <p class="text-2xl font-extrabold text-rose-950 dark:text-white">{{ $highCount }} <span class="text-xs font-medium text-slate-400">({{ $totalPriorityTasks > 0 ? (int)round($highCount/$totalPriorityTasks*100) : 0 }}%)</span></p>
                    </div>
                </div>

                <div class="flex items-center gap-3 rounded-2xl bg-amber-50/60 p-4 dark:bg-amber-950/30 border border-amber-100 dark:border-amber-900/40">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-500 text-white shadow-sm shadow-amber-500/20">
                        <x-icon name="flag" class="h-5 w-5" />
                    </span>
                    <div>
                        <span class="text-xs font-bold text-amber-800 dark:text-amber-300">Medium Priority</span>
                        <p class="text-2xl font-extrabold text-amber-950 dark:text-white">{{ $mediumCount }} <span class="text-xs font-medium text-slate-400">({{ $totalPriorityTasks > 0 ? (int)round($mediumCount/$totalPriorityTasks*100) : 0 }}%)</span></p>
                    </div>
                </div>

                <div class="flex items-center gap-3 rounded-2xl bg-emerald-50/60 p-4 dark:bg-emerald-950/30 border border-emerald-100 dark:border-emerald-900/40">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-500 text-white shadow-sm shadow-emerald-500/20">
                        <x-icon name="flag" class="h-5 w-5" />
                    </span>
                    <div>
                        <span class="text-xs font-bold text-emerald-800 dark:text-emerald-300">Low Priority</span>
                        <p class="text-2xl font-extrabold text-emerald-950 dark:text-white">{{ $lowCount }} <span class="text-xs font-medium text-slate-400">({{ $totalPriorityTasks > 0 ? (int)round($lowCount/$totalPriorityTasks*100) : 0 }}%)</span></p>
                    </div>
                </div>
            </div>
        </section>

        {{-- =========================================================
            PRIORITY MATRIX COLUMNS (3 COLUMNS)
        ========================================================== --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            @foreach ($columns as $key => $column)
                @php
                    $tasks = $tasksByPriority[$key];
                    $completedInTier = $tasks->where('status', 'completed')->count();
                    $tierProgress = $tasks->count() > 0 ? (int) round(($completedInTier / $tasks->count()) * 100) : 0;
                @endphp

                <div class="flex flex-col rounded-3xl border {{ $column['border'] }} bg-white shadow-sm dark:bg-slate-900 transition-colors duration-200 overflow-hidden">

                    {{-- Column Header --}}
                    <div class="bg-gradient-to-r {{ $column['header_bg'] }} p-5 border-b border-slate-100 dark:border-slate-800/80">
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-bold ring-1 {{ $column['badge'] }}">
                                <x-icon name="flag" class="h-3.5 w-3.5" />
                                {{ $column['title'] }}
                            </span>
                            <span class="text-xs font-extrabold text-slate-900 dark:text-white">
                                {{ $tasks->count() }} {{ Str::plural('task', $tasks->count()) }}
                            </span>
                        </div>

                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                            {{ $column['description'] }}
                        </p>

                        {{-- Progress bar --}}
                        <div class="mt-3.5 flex items-center justify-between text-[11px] text-slate-400">
                            <span>Completed</span>
                            <span class="font-bold text-slate-700 dark:text-slate-200">{{ $completedInTier }}/{{ $tasks->count() }} ({{ $tierProgress }}%)</span>
                        </div>
                        <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-slate-200/70 dark:bg-slate-800">
                            <div class="h-full rounded-full {{ $column['bar'] }} transition-all duration-500" style="width: {{ $tierProgress }}%"></div>
                        </div>
                    </div>

                    {{-- Column Cards --}}
                    <div class="flex-1 p-4 space-y-3.5 max-h-[750px] overflow-y-auto">
                        @forelse ($tasks as $task)
                            @php
                                $isCompleted = $task->status === 'completed';
                                $subtasksTotal = is_array($task->subtasks) ? count($task->subtasks) : 0;
                                $subtasksDone = is_array($task->subtasks) ? collect($task->subtasks)->where('completed', true)->count() : 0;
                            @endphp

                            <div class="group relative rounded-2xl border border-slate-200/80 bg-white p-4.5 transition-all duration-200 hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md dark:border-slate-800 dark:bg-slate-850 dark:hover:border-slate-700">
                                {{-- Card Top --}}
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-start gap-2.5 min-w-0">
                                        <form method="POST" action="{{ route('tasks.toggle-status', $task) }}" class="mt-0.5">
                                            @csrf @method('PATCH')
                                            <button type="submit"
                                                class="flex h-5.5 w-5.5 items-center justify-center rounded-lg border transition {{ $isCompleted ? 'bg-emerald-500 border-emerald-500 text-white' : 'border-slate-300 dark:border-slate-700 text-transparent hover:text-slate-400 hover:border-slate-400' }}">
                                                <x-icon name="check" class="h-3 w-3" />
                                            </button>
                                        </form>

                                        <div class="min-w-0">
                                            <a href="{{ route('tasks.show', $task) }}"
                                               class="text-sm font-bold text-slate-900 dark:text-white hover:text-blue-600 transition block truncate {{ $isCompleted ? 'line-through text-slate-400 dark:text-slate-500' : '' }}">
                                                {{ $task->title }}
                                            </a>

                                            @if ($task->category)
                                                <span class="mt-1 inline-block rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600 dark:bg-slate-750 dark:text-slate-300">
                                                    {{ $task->category }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    @if ($task->is_pinned)
                                        <x-icon name="pin" class="h-3.5 w-3.5 text-amber-500 shrink-0" />
                                    @endif
                                </div>

                                @if ($task->description)
                                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400 line-clamp-2">
                                        {{ $task->description }}
                                    </p>
                                @endif

                                {{-- Subtasks progress indicator --}}
                                @if ($subtasksTotal > 0)
                                    <div class="mt-3 rounded-lg bg-slate-50 p-2 dark:bg-slate-800/60">
                                        <div class="flex items-center justify-between text-[10px] font-bold text-slate-500 dark:text-slate-400">
                                            <span>Subtasks</span>
                                            <span>{{ $subtasksDone }}/{{ $subtasksTotal }}</span>
                                        </div>
                                        <div class="mt-1 h-1 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                                            <div class="h-full bg-blue-500 rounded-full" style="width: {{ (int)($subtasksDone/$subtasksTotal*100) }}%"></div>
                                        </div>
                                    </div>
                                @endif

                                {{-- Card Footer --}}
                                <div class="mt-3.5 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-slate-400">
                                    <div class="flex items-center gap-1.5">
                                        @if ($task->due_date)
                                            <span class="flex items-center gap-1 {{ $task->is_overdue ? 'text-rose-500 font-bold' : '' }}">
                                                <x-icon name="clock" class="h-3.5 w-3.5" />
                                                {{ $task->due_date->format('M d') }}
                                            </span>
                                        @else
                                            <span>No due date</span>
                                        @endif
                                    </div>

                                    <div class="flex items-center gap-1">
                                        <a href="{{ route('tasks.edit', $task) }}" data-edit-task-id="{{ $task->id }}" class="rounded-md p-1 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition">
                                            <x-icon name="edit" class="h-3.5 w-3.5" />
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="py-12 text-center text-xs text-slate-400">
                                No {{ $key }} priority tasks.
                            </div>
                        @endforelse
                    </div>

                    {{-- Add task to this tier --}}
                    <div class="p-3 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 text-center">
                        <button type="button" data-open-task-modal
                                class="inline-flex w-full items-center justify-center gap-1.5 rounded-xl border border-dashed border-slate-300 py-2.5 text-xs font-bold text-slate-600 transition hover:border-slate-400 hover:bg-white dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                            <x-icon name="plus" class="h-3.5 w-3.5" />
                            <span>Add {{ ucfirst($key) }} Priority Task</span>
                        </button>
                    </div>

                </div>
            @endforeach
        </div>

    </div>
@endsection
