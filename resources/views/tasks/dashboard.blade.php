@extends('layouts.app')

@section('title', 'Dashboard - Task Manager')

@section('content')
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

        $statusColors = [
            'pending' => 'bg-amber-50 text-amber-700 ring-amber-600/10 dark:bg-amber-950/40 dark:text-amber-300',
            'in_progress' => 'bg-blue-50 text-blue-700 ring-blue-600/10 dark:bg-blue-950/40 dark:text-blue-300',
            'completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/10 dark:bg-emerald-950/40 dark:text-emerald-300',
        ];

        $totalTasks = $stats['Total Tasks']['value'] ?? 0;
        $completedTasks = $stats['Completed']['value'] ?? 0;
    @endphp

    <div class="dashboard-motion min-h-screen space-y-7" data-dashboard>
        <div class="mx-auto max-w-[1600px] space-y-7">

            {{-- =========================================================
                HEADER / HERO
            ========================================================== --}}
            <section class="dashboard-hero relative overflow-hidden rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm sm:p-8 lg:p-9 dark:border-slate-800 dark:bg-slate-900">
                {{-- Decorative background --}}
                <div class="pointer-events-none absolute -right-28 -top-28 h-80 w-80 rounded-full bg-blue-100/70 blur-3xl dark:bg-blue-900/20"></div>
                <div class="pointer-events-none absolute right-44 top-16 h-36 w-36 rounded-full bg-indigo-100/50 blur-3xl dark:bg-indigo-900/20"></div>

                <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="max-w-2xl">
                        <div class="mb-4 flex items-center gap-2">
                            <span class="inline-flex h-8 items-center rounded-full bg-blue-50 px-3 text-xs font-bold uppercase tracking-wider text-blue-700 dark:bg-blue-950/60 dark:text-blue-400" data-i18n="workspace">
                                Workspace
                            </span>
                            <span class="text-xs font-medium text-slate-400" data-i18n="task_overview">
                                Task Overview
                            </span>
                        </div>

                        <h1 class="text-3xl font-extrabold tracking-tight text-slate-950 sm:text-4xl dark:text-white" data-i18n="welcome_dashboard">
                            Welcome to your Dashboard
                        </h1>

                        <p class="mt-3 max-w-xl text-sm leading-6 text-slate-500 sm:text-base dark:text-slate-400" data-i18n="dashboard_sub">
                            Stay focused on what matters most. Track deadlines, priority tasks, productivity insights, and team progress.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <a href="{{ route('calendar') }}"
                            class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                            <x-icon name="calendar" class="h-4 w-4" />
                            <span data-i18n="calendar">Calendar</span>
                        </a>

                        <button type="button" data-open-task-modal
                            class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-blue-950 px-5 text-sm font-semibold text-white shadow-lg shadow-blue-950/10 transition hover:-translate-y-0.5 hover:bg-blue-900 dark:bg-blue-600 dark:hover:bg-blue-500">
                            <x-icon name="plus" class="h-4 w-4" />
                            <span data-i18n="new_task">Add New Task</span>
                        </button>
                    </div>
                </div>
            </section>

            {{-- =========================================================
                OVERDUE TASKS BANNER (IF ANY)
            ========================================================== --}}
            @if (!empty($overdueCount) && $overdueCount > 0)
                <div class="flex items-center justify-between rounded-2xl border border-rose-200 bg-rose-50/90 p-4 text-rose-800 shadow-sm dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-200">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-600 dark:bg-rose-900/60 dark:text-rose-300">
                            <x-icon name="flag" class="h-5 w-5" />
                        </span>
                        <div>
                            <h3 class="text-sm font-bold" data-i18n="overdue_alert">
                                Attention: You have {{ $overdueCount }} overdue task{{ $overdueCount === 1 ? '' : 's' }}!
                            </h3>
                            <p class="text-xs text-rose-600 dark:text-rose-300" data-i18n="overdue_desc">
                                Some tasks missed their due dates. Review or reschedule them now.
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('tasks.index', ['status' => 'pending']) }}"
                        class="rounded-xl bg-rose-600 px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-rose-700">
                        <span data-i18n="view_overdue">View Tasks</span>
                    </a>
                </div>
            @endif

            {{-- =========================================================
                STAT CARDS & PRODUCTIVITY GAUGES
            ========================================================== --}}
            <section class="dashboard-stats grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($stats as $label => $stat)
                    <div class="dashboard-stat group relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-lg dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">
                                    {{ $label }}
                                </p>
                                <p class="mt-3 text-3xl font-bold tracking-tight text-slate-950 dark:text-white" data-dashboard-count="{{ $stat['value'] }}">
                                    {{ $stat['value'] }}
                                </p>
                                <p class="mt-2 text-xs font-medium text-slate-400">
                                    {{ $stat['label'] }}
                                </p>
                            </div>
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $stat['tone'] }} transition duration-300 group-hover:scale-105">
                                <x-icon :name="$stat['icon']" class="h-5 w-5" />
                            </span>
                        </div>
                    </div>
                @endforeach
            </section>

            {{-- =========================================================
                PRODUCTIVITY INSIGHTS: CHARTS & STREAK
            ========================================================== --}}
            <section class="grid gap-6 lg:grid-cols-3">
                {{-- 7-Day Activity Chart --}}
                <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm lg:col-span-2 dark:border-slate-800 dark:bg-slate-900">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-bold text-slate-950 dark:text-white" data-i18n="weekly_activity">
                                7-Day Activity & Progress
                            </h3>
                            <p class="text-xs text-slate-400" data-i18n="weekly_activity_sub">
                                Daily completed vs created tasks
                            </p>
                        </div>
                        <div class="flex items-center gap-4 text-xs font-semibold">
                            <span class="flex items-center gap-1.5 text-blue-600 dark:text-blue-400">
                                <span class="h-2.5 w-2.5 rounded-full bg-blue-500"></span> Created
                            </span>
                            <span class="flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400">
                                <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span> Completed
                            </span>
                        </div>
                    </div>

                    {{-- SVG Bar Chart --}}
                    <div class="mt-6 flex h-48 items-end justify-between gap-2 pt-6">
                        @foreach ($weeklyActivity as $item)
                            @php
                                $maxHeight = 120;
                                $createdHeight = max(8, min(120, $item['created'] * 24));
                                $completedHeight = max(8, min(120, $item['completed'] * 24));
                            @endphp
                            <div class="flex flex-1 flex-col items-center gap-2">
                                <div class="flex h-36 w-full items-end justify-center gap-1">
                                    {{-- Created bar --}}
                                    <div
                                        class="w-3 rounded-t-md bg-blue-400/80 transition-all hover:bg-blue-600 dark:bg-blue-600"
                                        style="height: {{ $createdHeight }}px"
                                        title="{{ $item['created'] }} created on {{ $item['date'] }}"
                                    ></div>
                                    {{-- Completed bar --}}
                                    <div
                                        class="w-3 rounded-t-md bg-emerald-500 transition-all hover:bg-emerald-600"
                                        style="height: {{ $completedHeight }}px"
                                        title="{{ $item['completed'] }} completed on {{ $item['date'] }}"
                                    ></div>
                                </div>
                                <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">{{ $item['day'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Productivity Gauge & Streak Card --}}
                <div class="flex flex-col justify-between rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div>
                        <h3 class="text-base font-bold text-slate-950 dark:text-white" data-i18n="completion_rate">
                            Completion Rate
                        </h3>
                        <p class="text-xs text-slate-400" data-i18n="productivity_score">
                            Your overall workspace productivity
                        </p>

                        {{-- Circular Progress Gauge --}}
                        <div class="relative my-6 flex items-center justify-center">
                            <svg class="h-32 w-32 -rotate-90 transform" viewBox="0 0 100 100">
                                <circle cx="50" cy="50" r="40" stroke="currentColor" stroke-width="10" class="text-slate-100 dark:text-slate-800" fill="transparent"/>
                                <circle cx="50" cy="50" r="40" stroke="currentColor" stroke-width="10" stroke-linecap="round"
                                    class="text-blue-600 transition-all duration-1000 dark:text-blue-400"
                                    fill="transparent"
                                    stroke-dasharray="251.2"
                                    stroke-dashoffset="{{ 251.2 - (251.2 * ($completionRate ?? 0)) / 100 }}"
                                />
                            </svg>
                            <div class="absolute flex flex-col items-center">
                                <span class="text-2xl font-extrabold text-slate-950 dark:text-white">{{ $completionRate }}%</span>
                                <span class="text-[10px] font-semibold text-slate-400" data-i18n="completed">Done</span>
                            </div>
                        </div>
                    </div>

                    {{-- Streak Badge --}}
                    <div class="flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50/80 p-3.5 dark:border-amber-900/60 dark:bg-amber-950/40">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-lg dark:bg-amber-900/60">
                            🔥
                        </span>
                        <div>
                            <p class="text-xs font-bold text-amber-900 dark:text-amber-200">
                                {{ $streakDays }} <span data-i18n="day_streak">Day Productivity Streak!</span>
                            </p>
                            <p class="text-[11px] text-amber-700/80 dark:text-amber-400" data-i18n="keep_streak">
                                Keep finishing tasks daily to maintain momentum.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            {{-- =========================================================
                MAIN GRID: PINNED, UPCOMING, & PRIORITY
            ========================================================== --}}
            <section class="grid gap-6 xl:grid-cols-[minmax(0,1.4fr)_minmax(340px,.8fr)]">

                {{-- Left Column: Upcoming Tasks --}}
                <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5 dark:border-slate-800">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400">
                                <x-icon name="calendar" class="h-5 w-5" />
                            </span>
                            <div>
                                <h3 class="text-base font-bold text-slate-950 dark:text-white" data-i18n="upcoming_schedule">
                                    Upcoming Schedule
                                </h3>
                                <p class="text-xs text-slate-400" data-i18n="next_deadlines">
                                    Your next deadlines and scheduled tasks.
                                </p>
                            </div>
                        </div>

                        <a href="{{ route('calendar') }}"
                            class="inline-flex items-center gap-1 text-xs font-bold text-blue-600 hover:text-blue-800 dark:text-blue-400">
                            <span data-i18n="view_calendar">View Calendar</span> →
                        </a>
                    </div>

                    @if ($upcomingTasks->isEmpty())
                        <div class="flex min-h-[300px] flex-col items-center justify-center p-8 text-center">
                            <x-icon name="calendar" class="h-10 w-10 text-slate-300 dark:text-slate-600" />
                            <p class="mt-3 text-sm font-bold text-slate-800 dark:text-slate-200" data-i18n="no_upcoming">No upcoming tasks scheduled</p>
                            <p class="mt-1 text-xs text-slate-400" data-i18n="add_due_dates">Add due dates to your tasks to see them here.</p>
                        </div>
                    @else
                        <div class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($upcomingTasks as $task)
                                <div class="flex items-center justify-between p-4 px-6 transition hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                    <div class="flex items-center gap-4 min-w-0">
                                        <button type="button" data-toggle-complete-id="{{ $task->id }}"
                                            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg border border-slate-300 bg-white hover:border-emerald-500 dark:border-slate-700 dark:bg-slate-800">
                                        </button>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-bold text-slate-900 dark:text-white">{{ $task->title }}</p>
                                            <div class="mt-1 flex items-center gap-2 text-xs text-slate-400">
                                                <span class="inline-flex items-center gap-1 {{ $task->is_overdue ? 'text-rose-600 font-bold' : '' }}">
                                                    <x-icon name="calendar" class="h-3 w-3" />
                                                    {{ $task->due_date->format('M d, Y') }}
                                                </span>
                                                @if ($task->category)
                                                    <span>•</span>
                                                    <span class="font-semibold text-slate-600 dark:text-slate-300">{{ $task->category }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $priorityColors[$task->priority] }}">
                                        {{ ucfirst($task->priority) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Right Column: High Priority Tasks --}}
                <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5 dark:border-slate-800">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400">
                                <x-icon name="flag" class="h-5 w-5" />
                            </span>
                            <div>
                                <h3 class="text-base font-bold text-slate-950 dark:text-white" data-i18n="high_priority">
                                    High Priority
                                </h3>
                                <p class="text-xs text-slate-400" data-i18n="critical_work">
                                    Critical items needing attention.
                                </p>
                            </div>
                        </div>

                        <a href="{{ route('priority') }}"
                            class="inline-flex items-center gap-1 text-xs font-bold text-blue-600 hover:text-blue-800 dark:text-blue-400">
                            <span data-i18n="view_all">View All</span> →
                        </a>
                    </div>

                    @if ($priorityTasks->isEmpty())
                        <div class="flex min-h-[300px] flex-col items-center justify-center p-8 text-center">
                            <x-icon name="flag" class="h-10 w-10 text-slate-300 dark:text-slate-600" />
                            <p class="mt-3 text-sm font-bold text-slate-800 dark:text-slate-200" data-i18n="no_high_priority">No high priority tasks</p>
                        </div>
                    @else
                        <div class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($priorityTasks as $task)
                                <div class="flex items-center justify-between p-4 px-6 transition hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                    <div class="min-w-0 pr-3">
                                        <p class="truncate text-sm font-bold text-slate-900 dark:text-white">{{ $task->title }}</p>
                                        <div class="mt-1 flex items-center gap-2 text-xs text-slate-400">
                                            @if ($task->due_date)
                                                <span>Due {{ $task->due_date->format('M d') }}</span>
                                            @endif
                                            @if ($task->category)
                                                <span>•</span>
                                                <span class="font-semibold text-slate-600 dark:text-slate-300">{{ $task->category }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <button type="button" data-edit-task-id="{{ $task->id }}"
                                        class="rounded-lg p-1.5 text-slate-400 hover:bg-blue-50 hover:text-blue-600 dark:hover:bg-slate-800">
                                        <x-icon name="edit" class="h-4 w-4" />
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

            </section>
        </div>
    </div>
@endsection
