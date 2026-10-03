@extends('layouts.app')

@section('content')
    @php
        $columns = [
            'high' => [
                'title' => 'High Priority',
                'description' => 'Urgent tasks that need attention first.',
                'iconTone' => 'bg-rose-50 text-rose-600 ring-rose-600/10 dark:bg-rose-950/40 dark:text-rose-400',
                'badgeTone' => 'bg-rose-50 text-rose-700 ring-rose-600/10 dark:bg-rose-950/50 dark:text-rose-300 dark:ring-rose-800',
                'dot' => 'bg-rose-500',
                'bar' => 'bg-rose-500',
            ],

            'medium' => [
                'title' => 'Medium Priority',
                'description' => 'Important work to complete soon.',
                'iconTone' => 'bg-amber-50 text-amber-600 ring-amber-600/10 dark:bg-amber-950/40 dark:text-amber-400',
                'badgeTone' => 'bg-amber-50 text-amber-700 ring-amber-600/10 dark:bg-amber-950/50 dark:text-amber-300 dark:ring-amber-800',
                'dot' => 'bg-amber-500',
                'bar' => 'bg-amber-500',
            ],

            'low' => [
                'title' => 'Low Priority',
                'description' => 'Routine work with flexible deadlines.',
                'iconTone' => 'bg-emerald-50 text-emerald-600 ring-emerald-600/10 dark:bg-emerald-950/40 dark:text-emerald-400',
                'badgeTone' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/10 dark:bg-emerald-950/50 dark:text-emerald-300 dark:ring-emerald-800',
                'dot' => 'bg-emerald-500',
                'bar' => 'bg-emerald-500',
            ],
        ];

        $statusColors = [
            'pending' => 'bg-slate-100 text-slate-600 ring-slate-500/10 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700',
            'in_progress' => 'bg-blue-50 text-blue-700 ring-blue-600/10 dark:bg-blue-950/50 dark:text-blue-300 dark:ring-blue-800',
            'completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/10 dark:bg-emerald-950/50 dark:text-emerald-300 dark:ring-emerald-800',
        ];

        $statusDots = [
            'pending' => 'bg-slate-400',
            'in_progress' => 'bg-blue-500',
            'completed' => 'bg-emerald-500',
        ];

        $highCount = $tasksByPriority['high']->count();
        $mediumCount = $tasksByPriority['medium']->count();
        $lowCount = $tasksByPriority['low']->count();

        $totalPriorityTasks = $highCount + $mediumCount + $lowCount;
    @endphp


    <div class="mx-auto max-w-[1500px]">

        {{-- =========================================================
            HERO HEADER
        ========================================================== --}}
        <section class="relative mb-6 overflow-hidden rounded-3xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900">

            {{-- Decorative background --}}
            <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-rose-100/50 blur-3xl dark:bg-rose-900/20"></div>
            <div class="pointer-events-none absolute right-48 top-10 h-36 w-36 rounded-full bg-blue-100/50 blur-3xl dark:bg-blue-900/20"></div>

            <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="mb-3 flex items-center gap-2">
                        <span class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-700 dark:bg-blue-950/60 dark:text-blue-400">
                            <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                            Priority
                        </span>
                    </div>

                    <h1 class="text-3xl font-extrabold tracking-tight text-slate-950 sm:text-4xl dark:text-white" data-i18n="priority">
                        Priority Board
                    </h1>

                    <p class="mt-3 max-w-xl text-sm leading-6 text-slate-500 sm:text-base dark:text-slate-400" data-i18n="critical_work">
                        Organize your workload by importance and focus on the tasks that matter most.
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('all-tasks') }}"
                        class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                        <x-icon name="list" class="h-4 w-4" />
                        <span data-i18n="all_tasks">All Tasks</span>
                    </a>

                    <a href="{{ route('tasks.index') }}#new-task" data-open-task-modal
                        class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-blue-950 px-5 text-sm font-semibold text-white shadow-lg shadow-blue-950/10 transition duration-200 hover:-translate-y-0.5 hover:bg-blue-900 dark:bg-blue-600 dark:hover:bg-blue-500">
                        <x-icon name="plus" class="h-4 w-4" />
                        <span data-i18n="new_task">Add Task</span>
                    </a>
                </div>
            </div>
        </section>

        {{-- =========================================================
            SUMMARY
        ========================================================== --}}
        <section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            {{-- Total --}}
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">
                            Total Tasks
                        </p>
                        <p class="mt-3 text-3xl font-bold tracking-tight text-slate-950 dark:text-white">
                            {{ $totalPriorityTasks }}
                        </p>
                        <p class="mt-1.5 text-xs font-medium text-slate-400">
                            Across all priorities
                        </p>
                    </div>
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400">
                        <x-icon name="clipboard" class="h-5 w-5" />
                    </span>
                </div>
            </div>

            {{-- High --}}
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">
                            High Priority
                        </p>
                        <p class="mt-3 text-3xl font-bold tracking-tight text-slate-950 dark:text-white">
                            {{ $highCount }}
                        </p>
                        <p class="mt-1.5 text-xs font-medium text-slate-400">
                            Needs attention
                        </p>
                    </div>
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400">
                        <x-icon name="flag" class="h-5 w-5" />
                    </span>
                </div>
            </div>

            {{-- Medium --}}
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">
                            Medium Priority
                        </p>
                        <p class="mt-3 text-3xl font-bold tracking-tight text-slate-950 dark:text-white">
                            {{ $mediumCount }}
                        </p>
                        <p class="mt-1.5 text-xs font-medium text-slate-400">
                            Plan to complete
                        </p>
                    </div>
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400">
                        <x-icon name="flag" class="h-5 w-5" />
                    </span>
                </div>
            </div>

            {{-- Low --}}
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">
                            Low Priority
                        </p>
                        <p class="mt-3 text-3xl font-bold tracking-tight text-slate-950 dark:text-white">
                            {{ $lowCount }}
                        </p>
                        <p class="mt-1.5 text-xs font-medium text-slate-400">
                            Flexible tasks
                        </p>
                    </div>
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                        <x-icon name="flag" class="h-5 w-5" />
                    </span>
                </div>
            </div>
        </section>

        {{-- =========================================================
            PRIORITY BOARD
        ========================================================== --}}
        <section class="grid items-start gap-5 xl:grid-cols-3">
            @foreach ($columns as $priority => $column)
                <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    {{-- COLUMN HEADER --}}
                    <div class="relative border-b border-slate-100 px-5 py-5 dark:border-slate-800">
                        <div class="absolute inset-x-0 top-0 h-1 {{ $column['bar'] }}"></div>

                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-start gap-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ring-1 ring-inset {{ $column['iconTone'] }}">
                                    <x-icon name="flag" class="h-5 w-5" />
                                </span>

                                <div>
                                    <h2 class="font-bold text-slate-950 dark:text-white">
                                        {{ $column['title'] }}
                                    </h2>
                                    <p class="mt-1 max-w-[220px] text-xs leading-5 text-slate-500 dark:text-slate-400">
                                        {{ $column['description'] }}
                                    </p>
                                </div>
                            </div>

                            {{-- Count --}}
                            <span class="inline-flex min-w-8 items-center justify-center rounded-lg px-2.5 py-1.5 text-xs font-bold ring-1 ring-inset {{ $column['badgeTone'] }}">
                                {{ $tasksByPriority[$priority]->count() }}
                            </span>
                        </div>
                    </div>

                    {{-- TASK LIST --}}
                    <div class="space-y-3 bg-slate-50/40 p-4 dark:bg-slate-950/40">
                        @forelse ($tasksByPriority[$priority] as $task)
                            <article class="group relative overflow-hidden rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700">
                                {{-- Left priority line --}}
                                <span class="absolute bottom-0 left-0 top-0 w-1 {{ $column['bar'] }}"></span>

                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        <h3 class="line-clamp-1 text-sm font-bold text-slate-950 dark:text-white">
                                            {{ $task->title }}
                                        </h3>

                                        @if ($task->description)
                                            <p class="mt-2 line-clamp-2 text-sm leading-6 text-slate-500 dark:text-slate-400">
                                                {{ $task->description }}
                                            </p>
                                        @else
                                            <p class="mt-2 text-xs italic text-slate-400 dark:text-slate-500">
                                                No description
                                            </p>
                                        @endif
                                    </div>

                                    {{-- Edit --}}
                                    <a href="{{ route('tasks.edit', $task) }}"
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 opacity-100 transition hover:bg-blue-50 hover:text-blue-600 lg:opacity-0 lg:group-hover:opacity-100 dark:hover:bg-slate-800 dark:hover:text-blue-400"
                                        aria-label="Edit {{ $task->title }}" title="Edit task">
                                        <x-icon name="edit" class="h-3.5 w-3.5" />
                                    </a>
                                </div>

                                {{-- BADGES --}}
                                <div class="mt-4 flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1 text-[10px] font-bold ring-1 ring-inset {{ $column['badgeTone'] }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $column['dot'] }}"></span>
                                        {{ ucfirst($priority) }}
                                    </span>

                                    <span class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1 text-[10px] font-bold ring-1 ring-inset {{ $statusColors[$task->status] }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $statusDots[$task->status] }}"></span>
                                        {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                                    </span>
                                </div>

                                {{-- FOOTER --}}
                                <div class="mt-4 flex items-center justify-between gap-3 border-t border-slate-100 pt-3 dark:border-slate-800">
                                    <span class="inline-flex min-w-0 items-center gap-1.5 text-xs font-medium text-slate-400">
                                        <x-icon name="calendar" class="h-3.5 w-3.5 shrink-0" />
                                        @if ($task->due_date)
                                            <span class="truncate">
                                                {{ $task->due_date->format('M d, Y') }}
                                                @if ($task->end_date)
                                                    - {{ $task->end_date->format('M d, Y') }}
                                                @endif
                                            </span>
                                        @else
                                            <span>No due date</span>
                                        @endif
                                    </span>

                                    @if ($task->status === 'completed')
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-600 dark:text-emerald-400">
                                            <x-icon name="check-circle" class="h-3.5 w-3.5" />
                                            Done
                                        </span>
                                    @endif
                                </div>
                            </article>
                        @empty
                            <div class="flex min-h-[220px] flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-200 bg-white/60 p-6 text-center dark:border-slate-800 dark:bg-slate-900/60">
                                <span class="flex h-11 w-11 items-center justify-center rounded-xl {{ $column['iconTone'] }}">
                                    <x-icon name="flag" class="h-5 w-5" />
                                </span>
                                <p class="mt-3 text-sm font-bold text-slate-800 dark:text-slate-200">
                                    No {{ $priority }} priority tasks
                                </p>
                                <p class="mt-1 max-w-[210px] text-xs leading-5 text-slate-400">
                                    Tasks marked as {{ $priority }} priority will appear in this column.
                                </p>
                                <a href="{{ route('tasks.index') }}#new-task" data-open-task-modal
                                    class="mt-4 inline-flex items-center gap-1.5 text-xs font-bold text-blue-600 transition hover:text-blue-800 dark:text-blue-400">
                                    <x-icon name="plus" class="h-3.5 w-3.5" />
                                    Add Task
                                </a>
                            </div>
                        @endforelse
                    </div>

                    {{-- COLUMN FOOTER --}}
                    @if ($tasksByPriority[$priority]->isNotEmpty())
                        <div class="border-t border-slate-100 bg-white px-4 py-3 dark:border-slate-800 dark:bg-slate-900">
                            <a href="{{ route('tasks.index', ['priority' => $priority]) }}"
                                class="flex items-center justify-center gap-1.5 rounded-lg py-2 text-xs font-bold text-slate-500 transition hover:bg-slate-50 hover:text-blue-600 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-blue-400">
                                View {{ ucfirst($priority) }} Tasks
                                <span aria-hidden="true">→</span>
                            </a>
                        </div>
                    @endif
                </div>
            @endforeach
        </section>
    </div>
@endsection
