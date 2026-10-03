@extends('layouts.app')

@section('content')
    @php
        $priorityColors = [
            'low' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/10 dark:bg-emerald-950/60 dark:text-emerald-400 dark:ring-emerald-500/20',
            'medium' => 'bg-amber-50 text-amber-700 ring-amber-600/10 dark:bg-amber-950/60 dark:text-amber-400 dark:ring-amber-500/20',
            'high' => 'bg-rose-50 text-rose-700 ring-rose-600/10 dark:bg-rose-950/60 dark:text-rose-400 dark:ring-rose-500/20',
        ];

        $priorityDots = [
            'low' => 'bg-emerald-500',
            'medium' => 'bg-amber-500',
            'high' => 'bg-rose-500',
        ];

        $statusColors = [
            'pending' => 'bg-slate-100 text-slate-600 ring-slate-500/10 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700',
            'in_progress' => 'bg-blue-50 text-blue-700 ring-blue-600/10 dark:bg-blue-950/60 dark:text-blue-400 dark:ring-blue-500/20',
            'completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/10 dark:bg-emerald-950/60 dark:text-emerald-400 dark:ring-emerald-500/20',
        ];

        $scheduledCount = $groupedTasks->sum(fn($tasks) => $tasks->count());
        $unscheduledCount = $unscheduledTasks->count();
        $totalCount = $scheduledCount + $unscheduledCount;
    @endphp

    <div class="mx-auto max-w-[1500px]">

        {{-- =========================================================
            HERO
        ========================================================== --}}
        <section class="relative mb-6 overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 transition-colors duration-200">
            <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-blue-100/60 blur-3xl dark:bg-blue-900/20"></div>
            <div class="pointer-events-none absolute right-52 top-12 h-32 w-32 rounded-full bg-violet-100/60 blur-3xl dark:bg-violet-900/20"></div>

            <div class="relative flex flex-col gap-6 px-6 py-7 sm:px-8 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="mb-3 flex items-center gap-2">
                        <span class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-700 dark:bg-blue-950/70 dark:text-blue-400">
                            <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                            <span data-i18n="schedule">Schedule</span>
                        </span>
                    </div>

                    <h1 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl dark:text-white" data-i18n="calendar">
                        Calendar
                    </h1>

                    <p class="mt-3 max-w-xl text-sm leading-6 text-slate-500 sm:text-base dark:text-slate-400" data-i18n="next_deadlines">
                        Keep track of upcoming deadlines and organize tasks by due date.
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('all-tasks') }}"
                        class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white">
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
            CALENDAR STATS
        ========================================================== --}}
        <section class="mb-6 grid gap-4 grid-cols-1 sm:grid-cols-3">
            {{-- Total --}}
            <div class="group rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-500 dark:text-slate-400" data-i18n="total_tasks">
                            Total Tasks
                        </p>
                        <p class="mt-2 text-3xl font-bold tracking-tight text-slate-950 dark:text-white">
                            {{ $totalCount }}
                        </p>
                        <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">
                            All calendar work
                        </p>
                    </div>

                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-violet-50 text-violet-600 dark:bg-violet-950/60 dark:text-violet-400">
                        <x-icon name="clipboard" class="h-5 w-5" />
                    </span>
                </div>
            </div>

            {{-- Scheduled --}}
            <div class="group rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">
                            Scheduled
                        </p>
                        <p class="mt-2 text-3xl font-bold tracking-tight text-slate-950 dark:text-white">
                            {{ $scheduledCount }}
                        </p>
                        <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">
                            Tasks with due dates
                        </p>
                    </div>

                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400">
                        <x-icon name="calendar" class="h-5 w-5" />
                    </span>
                </div>
            </div>

            {{-- Unscheduled --}}
            <div class="group rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">
                            Unscheduled
                        </p>
                        <p class="mt-2 text-3xl font-bold tracking-tight text-slate-950 dark:text-white">
                            {{ $unscheduledCount }}
                        </p>
                        <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">
                            Need a due date
                        </p>
                    </div>

                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400">
                        <x-icon name="activity" class="h-5 w-5" />
                    </span>
                </div>
            </div>
        </section>

        {{-- =========================================================
            MAIN GRID
        ========================================================== --}}
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">

            {{-- =====================================================
                SCHEDULED TASKS
            ====================================================== --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 transition-colors duration-200">
                {{-- Header --}}
                <div class="flex flex-col gap-4 border-b border-slate-100 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6 dark:border-slate-800">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400">
                            <x-icon name="calendar" class="h-5 w-5" />
                        </span>

                        <div>
                            <h2 class="font-bold text-slate-950 dark:text-white">
                                Scheduled Tasks
                            </h2>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                Deadlines grouped by date
                            </p>
                        </div>
                    </div>

                    <div class="inline-flex w-fit items-center gap-2 rounded-lg bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-500 dark:bg-slate-800 dark:text-slate-300">
                        <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                        {{ $scheduledCount }} {{ Str::plural('task', $scheduledCount) }}
                    </div>
                </div>

                {{-- Scheduled Content --}}
                @forelse ($groupedTasks as $date => $tasks)
                    @php
                        $calendarDate = \Carbon\Carbon::parse($date);
                        $isToday = $calendarDate->isToday();
                        $isPast = $calendarDate->isPast() && !$calendarDate->isToday();
                        $dateTone = $isToday
                            ? 'bg-blue-950 text-white dark:bg-blue-600'
                            : ($isPast
                                ? 'bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400'
                                : 'bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400');
                    @endphp

                    <div class="border-b border-slate-100 px-5 py-6 last:border-b-0 sm:px-6 dark:border-slate-800">
                        {{-- DATE HEADER --}}
                        <div class="mb-5 flex items-center justify-between gap-4">
                            <div class="flex items-center gap-4">
                                {{-- Date Box --}}
                                <div class="flex h-16 w-16 shrink-0 flex-col items-center justify-center rounded-2xl {{ $dateTone }}">
                                    <span class="text-[10px] font-bold uppercase tracking-wider">
                                        {{ $calendarDate->format('M') }}
                                    </span>
                                    <span class="mt-0.5 text-xl font-bold leading-none">
                                        {{ $calendarDate->format('d') }}
                                    </span>
                                </div>

                                {{-- Date labels --}}
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="font-bold text-slate-950 dark:text-white">
                                            {{ $calendarDate->format('l') }}
                                        </h3>

                                        @if ($isToday)
                                            <span class="rounded-lg bg-blue-100 px-2 py-1 text-[10px] font-bold uppercase tracking-wide text-blue-700 dark:bg-blue-950 dark:text-blue-300">
                                                Today
                                            </span>
                                        @elseif ($isPast)
                                            <span class="rounded-lg bg-rose-50 px-2 py-1 text-[10px] font-bold uppercase tracking-wide text-rose-600 dark:bg-rose-950/60 dark:text-rose-400">
                                                Overdue
                                            </span>
                                        @endif
                                    </div>

                                    <p class="mt-1 text-xs font-medium text-slate-400 dark:text-slate-500">
                                        {{ $calendarDate->format('F d, Y') }}
                                        ·
                                        {{ $tasks->count() }}
                                        {{ Str::plural('task', $tasks->count()) }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        {{-- TASK CARDS --}}
                        <div class="space-y-3">
                            @foreach ($tasks as $task)
                                <div class="group relative rounded-xl border border-slate-100 bg-slate-50/70 p-4 transition duration-200 hover:border-slate-200 hover:bg-white hover:shadow-sm dark:border-slate-800/80 dark:bg-slate-800/50 dark:hover:border-slate-700 dark:hover:bg-slate-800">
                                    {{-- Top --}}
                                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <h4 class="font-bold text-slate-950 dark:text-white">
                                                    {{ $task->title }}
                                                </h4>

                                                {{-- Priority --}}
                                                @if (isset($task->priority))
                                                    <span class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1 text-[10px] font-bold ring-1 ring-inset {{ $priorityColors[$task->priority] ?? '' }}">
                                                        <span class="h-1.5 w-1.5 rounded-full {{ $priorityDots[$task->priority] ?? '' }}"></span>
                                                        {{ ucfirst($task->priority) }}
                                                    </span>
                                                @endif
                                            </div>

                                            @if ($task->description)
                                                <p class="mt-2 line-clamp-2 text-sm leading-6 text-slate-500 dark:text-slate-400">
                                                    {{ $task->description }}
                                                </p>
                                            @endif
                                        </div>

                                        {{-- Status --}}
                                        <span class="inline-flex w-fit shrink-0 rounded-lg px-2.5 py-1.5 text-xs font-bold ring-1 ring-inset {{ $statusColors[$task->status] ?? '' }}">
                                            {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                                        </span>
                                    </div>

                                    {{-- Footer --}}
                                    <div class="mt-4 flex items-center justify-between border-t border-slate-200/70 pt-3 dark:border-slate-700/60">
                                        <span class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-400 dark:text-slate-400">
                                            <x-icon name="calendar" class="h-3.5 w-3.5" />
                                            Due {{ $calendarDate->format('M d, Y') }}
                                            @if ($task->end_date)
                                                - End {{ $task->end_date->format('M d, Y') }}
                                            @endif
                                        </span>

                                        <button type="button" data-edit-task-id="{{ $task->id }}"
                                            class="inline-flex h-8 items-center gap-1.5 rounded-lg px-2.5 text-xs font-bold text-slate-500 transition hover:bg-blue-50 hover:text-blue-600 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-blue-400">
                                            <x-icon name="edit" class="h-3.5 w-3.5" />
                                            <span data-i18n="edit">Edit</span>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                @empty
                    {{-- EMPTY CALENDAR --}}
                    <div class="flex min-h-[430px] flex-col items-center justify-center px-6 py-16 text-center">
                        <div class="mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400">
                            <x-icon name="calendar" class="h-7 w-7" />
                        </div>

                        <h3 class="text-lg font-bold text-slate-950 dark:text-white">
                            Your calendar is empty
                        </h3>

                        <p class="mt-2 max-w-sm text-sm leading-6 text-slate-500 dark:text-slate-400">
                            Set a due date on a task and it will automatically appear in your schedule.
                        </p>

                        <a href="{{ route('tasks.index') }}#new-task" data-open-task-modal
                            class="mt-6 inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-blue-950 px-4 text-sm font-semibold text-white transition hover:bg-blue-900 dark:bg-blue-600 dark:hover:bg-blue-500">
                            <x-icon name="plus" class="h-4 w-4" />
                            Add Scheduled Task
                        </a>
                    </div>
                @endforelse
            </section>

            {{-- =====================================================
                RIGHT SIDEBAR (UNSCHEDULED TASKS)
            ====================================================== --}}
            <aside class="space-y-6">
                {{-- UNSCHEDULED TASKS --}}
                <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 transition-colors duration-200">
                    {{-- Header --}}
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-5 dark:border-slate-800">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400">
                                <x-icon name="activity" class="h-5 w-5" />
                            </span>

                            <div>
                                <h2 class="font-bold text-slate-950 dark:text-white">
                                    Unscheduled
                                </h2>
                                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                    Tasks without deadlines
                                </p>
                            </div>
                        </div>

                        <span class="flex h-7 min-w-7 items-center justify-center rounded-lg bg-slate-100 px-2 text-xs font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                            {{ $unscheduledCount }}
                        </span>
                    </div>

                    <div class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($unscheduledTasks as $task)
                            <div class="group px-5 py-4 transition hover:bg-slate-50/70 dark:hover:bg-slate-800/50">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-bold text-slate-950 dark:text-white">
                                            {{ $task->title }}
                                        </p>

                                        <div class="mt-2 flex flex-wrap items-center gap-2">
                                            @if (isset($task->priority))
                                                <span class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1 text-[10px] font-bold ring-1 ring-inset {{ $priorityColors[$task->priority] ?? '' }}">
                                                    <span class="h-1.5 w-1.5 rounded-full {{ $priorityDots[$task->priority] ?? '' }}"></span>
                                                    {{ ucfirst($task->priority) }}
                                                </span>
                                            @endif

                                            @if (isset($task->status))
                                                <span class="rounded-lg px-2 py-1 text-[10px] font-bold ring-1 ring-inset {{ $statusColors[$task->status] ?? '' }}">
                                                    {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <button type="button" data-edit-task-id="{{ $task->id }}"
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-blue-50 hover:text-blue-600 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-blue-400"
                                        aria-label="Edit {{ $task->title }}">
                                        <x-icon name="edit" class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="px-5 py-10 text-center">
                                <span class="mx-auto flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                                    <x-icon name="check-circle" class="h-5 w-5" />
                                </span>

                                <p class="mt-3 text-sm font-bold text-slate-900 dark:text-white">
                                    Everything is scheduled
                                </p>

                                <p class="mt-1 text-xs leading-5 text-slate-400 dark:text-slate-500">
                                    All your tasks already have due dates.
                                </p>
                            </div>
                        @endforelse
                    </div>
                </section>

                {{-- CALENDAR TIP --}}
                <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-950 via-blue-900 to-indigo-900 p-5 text-white shadow-lg shadow-blue-950/10 dark:from-slate-900 dark:via-blue-950 dark:to-slate-900 border border-transparent dark:border-slate-800">
                    <div class="pointer-events-none absolute -right-10 -top-10 h-28 w-28 rounded-full bg-blue-400/20 blur-2xl"></div>

                    <div class="relative">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 text-blue-100 ring-1 ring-white/10">
                            <x-icon name="calendar" class="h-5 w-5" />
                        </span>

                        <h3 class="mt-4 font-bold">
                            Plan ahead
                        </h3>

                        <p class="mt-2 text-xs leading-5 text-blue-100/70">
                            Add due dates to important tasks so deadlines stay visible on your calendar.
                        </p>

                        <a href="{{ route('tasks.index') }}#new-task" data-open-task-modal
                            class="mt-4 inline-flex items-center gap-1.5 text-xs font-bold text-white">
                            Create task
                            <span aria-hidden="true">→</span>
                        </a>
                    </div>
                </section>
            </aside>
        </div>
    </div>
@endsection
