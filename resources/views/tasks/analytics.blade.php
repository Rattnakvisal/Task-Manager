@extends('layouts.app')

@section('title', 'Analytics & Productivity - Task Manager')

@section('content')
    <div class="mx-auto max-w-[1500px] space-y-6">

        {{-- =========================================================
            HERO HEADER
        ========================================================== --}}
        <section class="relative overflow-hidden rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm sm:p-8 dark:border-slate-800 dark:bg-slate-900 transition-colors duration-200">
            <div class="pointer-events-none absolute -right-20 -top-20 h-64 w-64 rounded-full bg-blue-100/70 blur-3xl dark:bg-blue-900/20"></div>
            <div class="pointer-events-none absolute right-64 top-10 h-40 w-40 rounded-full bg-indigo-100/60 blur-3xl dark:bg-indigo-900/20"></div>

            <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="mb-3 flex items-center gap-2">
                        <span class="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-700 dark:bg-indigo-950/70 dark:text-indigo-400">
                            <span class="h-2 w-2 rounded-full bg-indigo-500 animate-pulse"></span>
                            <span data-i18n="analytics">Workspace Intelligence</span>
                        </span>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                            {{ $total }} {{ Str::plural('Task', $total) }} tracked
                        </span>
                    </div>

                    <h1 class="text-3xl font-extrabold tracking-tight text-slate-950 sm:text-4xl dark:text-white" data-i18n="analytics">
                        Analytics & Productivity
                    </h1>

                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500 sm:text-base dark:text-slate-400" data-i18n="analytics_sub">
                        Track performance, completion rates, priority distribution, and workspace productivity trends.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    {{-- Export Menu --}}
                    <div class="flex items-center rounded-xl border border-slate-200 bg-white p-1 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                        <span class="flex items-center gap-1 px-2.5 text-xs font-bold text-slate-400">
                            <x-icon name="download" class="h-3.5 w-3.5" />
                            <span data-i18n="export">Export</span>:
                        </span>
                        <a href="{{ route('tasks.export', 'csv') }}"
                           class="inline-flex h-9 items-center rounded-lg px-2.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white"
                           title="Export tasks to CSV">
                            CSV
                        </a>
                        <span class="text-slate-200 dark:text-slate-700">|</span>
                        <a href="{{ route('tasks.export', 'json') }}"
                           class="inline-flex h-9 items-center rounded-lg px-2.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white"
                           title="Export tasks to JSON">
                            JSON
                        </a>
                    </div>

                    <button type="button" onclick="window.location.reload()"
                        class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                        <x-icon name="refresh-cw" class="h-4 w-4 text-slate-500" />
                        <span>Refresh</span>
                    </button>

                    <a href="{{ route('tasks.index') }}"
                        class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-blue-950 px-5 text-sm font-semibold text-white shadow-lg shadow-blue-950/10 transition duration-200 hover:-translate-y-0.5 hover:bg-blue-900 dark:bg-blue-600 dark:hover:bg-blue-500">
                        <x-icon name="kanban" class="h-4 w-4" />
                        <span data-i18n="my_tasks">Open Board</span>
                    </a>
                </div>
            </div>
        </section>

        {{-- =========================================================
            KEY METRICS GRID (4 CARDS)
        ========================================================== --}}
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">

            {{-- 1. Productivity Score --}}
            <div class="relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Productivity Score</span>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400">
                        <x-icon name="zap" class="h-4.5 w-4.5" />
                    </span>
                </div>
                <div class="mt-4 flex items-baseline gap-2">
                    <span class="text-3xl font-extrabold text-slate-950 dark:text-white">{{ $productivityScore }}</span>
                    <span class="text-sm font-semibold text-slate-400">/ 100</span>
                    <span class="ml-auto inline-flex items-center rounded-full px-2 py-0.5 text-xs font-bold {{ $productivityScore >= 70 ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400' : ($productivityScore >= 40 ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400' : 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400') }}">
                        {{ $productivityScore >= 80 ? 'Optimal' : ($productivityScore >= 60 ? 'Healthy' : ($productivityScore >= 40 ? 'Moderate' : 'Needs Focus')) }}
                    </span>
                </div>
                <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                    <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-blue-500 transition-all duration-500" style="width: {{ $productivityScore }}%"></div>
                </div>
                <p class="mt-2.5 text-xs text-slate-500 dark:text-slate-400">Calculated from completion rate & delivery momentum</p>
            </div>

            {{-- 2. Completion Rate --}}
            <div class="relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500" data-i18n="completion_rate">Completion Rate</span>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                        <x-icon name="check-circle" class="h-4.5 w-4.5" />
                    </span>
                </div>
                <div class="mt-4 flex items-baseline gap-2">
                    <span class="text-3xl font-extrabold text-slate-950 dark:text-white">{{ $completionRate }}%</span>
                    <span class="text-xs font-semibold text-slate-400">({{ $completed }}/{{ $total }} done)</span>
                </div>
                <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                    <div class="h-full rounded-full bg-emerald-500 transition-all duration-500" style="width: {{ $completionRate }}%"></div>
                </div>
                <p class="mt-2.5 text-xs text-slate-500 dark:text-slate-400">{{ $completed }} tasks marked as finished</p>
            </div>

            {{-- 3. Active Work Velocity --}}
            <div class="relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Active Workload</span>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-cyan-50 text-cyan-600 dark:bg-cyan-950/60 dark:text-cyan-400">
                        <x-icon name="layers" class="h-4.5 w-4.5" />
                    </span>
                </div>
                <div class="mt-4 flex items-baseline gap-2">
                    <span class="text-3xl font-extrabold text-slate-950 dark:text-white">{{ $inProgress + $pending }}</span>
                    <span class="text-xs font-semibold text-slate-400">active tasks</span>
                    <span class="ml-auto inline-flex items-center gap-1 rounded-full bg-cyan-50 px-2 py-0.5 text-xs font-bold text-cyan-700 dark:bg-cyan-950/60 dark:text-cyan-400">
                        {{ $inProgress }} in progress
                    </span>
                </div>
                <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                    <div class="h-full rounded-full bg-cyan-500 transition-all duration-500" style="width: {{ $total > 0 ? (int)(($inProgress + $pending) / $total * 100) : 0 }}%"></div>
                </div>
                <p class="mt-2.5 text-xs text-slate-500 dark:text-slate-400">{{ $pending }} pending · {{ $inProgress }} in progress</p>
            </div>

            {{-- 4. Deadline Health & Overdue --}}
            <div class="relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Deadline Health</span>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl {{ $overdueCount > 0 ? 'bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400' : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400' }}">
                        <x-icon name="clock" class="h-4.5 w-4.5" />
                    </span>
                </div>
                <div class="mt-4 flex items-baseline gap-2">
                    <span class="text-3xl font-extrabold {{ $overdueCount > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-950 dark:text-white' }}">{{ $overdueCount }}</span>
                    <span class="text-xs font-semibold text-slate-400">overdue</span>
                    <span class="ml-auto inline-flex items-center rounded-full px-2 py-0.5 text-xs font-bold {{ $overdueCount === 0 ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400' : 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400' }}">
                        {{ $onTimeRate }}% On Time
                    </span>
                </div>
                <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                    <div class="h-full rounded-full {{ $overdueCount > 0 ? 'bg-rose-500' : 'bg-emerald-500' }} transition-all duration-500" style="width: {{ $onTimeRate }}%"></div>
                </div>
                <p class="mt-2.5 text-xs text-slate-500 dark:text-slate-400">{{ $overdueCount > 0 ? 'Needs attention to meet deadlines' : 'Great job! Zero overdue tasks' }}</p>
            </div>

        </div>

        {{-- =========================================================
            CHARTS & INSIGHTS ROW (2 COLUMNS)
        ========================================================== --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            {{-- Activity & Progress Trends (2 Cols) --}}
            <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm lg:col-span-2 dark:border-slate-800 dark:bg-slate-900 transition-colors duration-200">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-950 dark:text-white" data-i18n="weekly_activity">
                            7-Day Activity & Progress
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400" data-i18n="weekly_activity_sub">
                            Daily comparison of created vs completed tasks
                        </p>
                    </div>

                    {{-- Chart Legend --}}
                    <div class="flex items-center gap-4 text-xs font-semibold">
                        <span class="flex items-center gap-1.5 text-slate-600 dark:text-slate-300">
                            <span class="h-2.5 w-2.5 rounded-full bg-blue-600"></span>
                            Created
                        </span>
                        <span class="flex items-center gap-1.5 text-slate-600 dark:text-slate-300">
                            <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                            Completed
                        </span>
                    </div>
                </div>

                {{-- Interactive Bar Chart --}}
                <div class="mt-8">
                    @php
                        $maxVal = 1;
                        foreach ($weeklyActivity as $d) {
                            $maxVal = max($maxVal, $d['created'], $d['completed']);
                        }
                    @endphp

                    <div class="grid grid-cols-7 gap-3 sm:gap-6 items-end h-56 pt-6 pb-2 border-b border-slate-100 dark:border-slate-800">
                        @foreach ($weeklyActivity as $day)
                            @php
                                $createHeight = (int) round(($day['created'] / $maxVal) * 160);
                                $completeHeight = (int) round(($day['completed'] / $maxVal) * 160);
                            @endphp
                            <div class="group relative flex flex-col items-center justify-end h-full">
                                {{-- Hover Tooltip --}}
                                <div class="pointer-events-none absolute -top-12 z-20 hidden rounded-xl bg-slate-900 px-3 py-1.5 text-center text-[11px] font-bold text-white shadow-xl group-hover:block dark:bg-white dark:text-slate-900 whitespace-nowrap">
                                    {{ $day['date'] }}: +{{ $day['created'] }} new, {{ $day['completed'] }} done
                                </div>

                                {{-- Dual Bars --}}
                                <div class="flex items-end gap-1.5 h-full w-full justify-center">
                                    {{-- Created bar --}}
                                    <div
                                        class="w-3 sm:w-4 rounded-t-lg bg-blue-500/80 hover:bg-blue-600 transition-all duration-300 group-hover:scale-105"
                                        style="height: {{ max(6, $createHeight) }}px;"
                                        title="{{ $day['created'] }} created"
                                    ></div>
                                    {{-- Completed bar --}}
                                    <div
                                        class="w-3 sm:w-4 rounded-t-lg bg-emerald-500 hover:bg-emerald-600 transition-all duration-300 group-hover:scale-105"
                                        style="height: {{ max(6, $completeHeight) }}px;"
                                        title="{{ $day['completed'] }} completed"
                                    ></div>
                                </div>

                                {{-- Day label --}}
                                <span class="mt-3 text-xs font-semibold text-slate-500 group-hover:text-slate-900 dark:text-slate-400 dark:group-hover:text-white">
                                    {{ $day['day'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4 flex flex-wrap items-center justify-between text-xs text-slate-400">
                        <span>Showing past 7 calendar days</span>
                        <span class="font-medium text-slate-600 dark:text-slate-300">Peak single-day tasks: {{ $maxVal }}</span>
                    </div>
                </div>
            </div>

            {{-- Priority Breakdown (1 Col) --}}
            <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 transition-colors duration-200 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-bold text-slate-950 dark:text-white">
                            Priority Distribution
                        </h2>
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400">
                            <x-icon name="flag" class="h-4 w-4" />
                        </span>
                    </div>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Balance across urgency levels
                    </p>

                    <div class="mt-6 space-y-4">
                        {{-- High Priority --}}
                        @php
                            $highPct = $total > 0 ? (int) round(($highCount / $total) * 100) : 0;
                            $medPct = $total > 0 ? (int) round(($mediumCount / $total) * 100) : 0;
                            $lowPct = $total > 0 ? (int) round(($lowCount / $total) * 100) : 0;
                        @endphp

                        <div>
                            <div class="flex items-center justify-between text-xs font-bold">
                                <span class="flex items-center gap-1.5 text-rose-600 dark:text-rose-400">
                                    <span class="h-2 w-2 rounded-full bg-rose-500"></span>
                                    High Priority
                                </span>
                                <span class="text-slate-700 dark:text-slate-300">{{ $highCount }} ({{ $highPct }}%)</span>
                            </div>
                            <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                <div class="h-full rounded-full bg-rose-500 transition-all duration-300" style="width: {{ $highPct }}%"></div>
                            </div>
                        </div>

                        {{-- Medium Priority --}}
                        <div>
                            <div class="flex items-center justify-between text-xs font-bold">
                                <span class="flex items-center gap-1.5 text-amber-600 dark:text-amber-400">
                                    <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                                    Medium Priority
                                </span>
                                <span class="text-slate-700 dark:text-slate-300">{{ $mediumCount }} ({{ $medPct }}%)</span>
                            </div>
                            <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                <div class="h-full rounded-full bg-amber-500 transition-all duration-300" style="width: {{ $medPct }}%"></div>
                            </div>
                        </div>

                        {{-- Low Priority --}}
                        <div>
                            <div class="flex items-center justify-between text-xs font-bold">
                                <span class="flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400">
                                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                    Low Priority
                                </span>
                                <span class="text-slate-700 dark:text-slate-300">{{ $lowCount }} ({{ $lowPct }}%)</span>
                            </div>
                            <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                <div class="h-full rounded-full bg-emerald-500 transition-all duration-300" style="width: {{ $lowPct }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <a href="{{ route('priority') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white py-2.5 text-xs font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-750">
                        <x-icon name="flag" class="h-3.5 w-3.5" />
                        <span>View Priority Board</span>
                    </a>
                </div>
            </div>

        </div>

        {{-- =========================================================
            CATEGORY & PROJECT PERFORMANCE
        ========================================================== --}}
        <section class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 transition-colors duration-200">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
                <div>
                    <h2 class="text-lg font-bold text-slate-950 dark:text-white">
                        Performance by Category & Project
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Track execution speed and completed tasks per project area
                    </p>
                </div>
                <a href="{{ route('projects') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-blue-600 hover:text-blue-700 dark:text-blue-400">
                    <span>Manage Projects</span>
                    <span>→</span>
                </a>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($categoryStats as $stat)
                    @php
                        $catTheme = match($stat['name']) {
                            'Work' => ['border' => 'border-indigo-100 dark:border-indigo-900/40', 'bg' => 'bg-indigo-50/50 dark:bg-indigo-950/20', 'bar' => 'bg-indigo-600', 'badge' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300'],
                            'Dev' => ['border' => 'border-sky-100 dark:border-sky-900/40', 'bg' => 'bg-sky-50/50 dark:bg-sky-950/20', 'bar' => 'bg-sky-500', 'badge' => 'bg-sky-50 text-sky-700 dark:bg-sky-950 dark:text-sky-300'],
                            'Design' => ['border' => 'border-pink-100 dark:border-pink-900/40', 'bg' => 'bg-pink-50/50 dark:bg-pink-950/20', 'bar' => 'bg-pink-500', 'badge' => 'bg-pink-50 text-pink-700 dark:bg-pink-950 dark:text-pink-300'],
                            'Personal' => ['border' => 'border-purple-100 dark:border-purple-900/40', 'bg' => 'bg-purple-50/50 dark:bg-purple-950/20', 'bar' => 'bg-purple-500', 'badge' => 'bg-purple-50 text-purple-700 dark:bg-purple-950 dark:text-purple-300'],
                            'Urgent' => ['border' => 'border-rose-100 dark:border-rose-900/40', 'bg' => 'bg-rose-50/50 dark:bg-rose-950/20', 'bar' => 'bg-rose-500', 'badge' => 'bg-rose-50 text-rose-700 dark:bg-rose-950 dark:text-rose-400'],
                            default => ['border' => 'border-slate-100 dark:border-slate-800', 'bg' => 'bg-slate-50/50 dark:bg-slate-800/40', 'bar' => 'bg-blue-600', 'badge' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300']
                        };
                    @endphp
                    <div class="rounded-2xl border {{ $catTheme['border'] }} {{ $catTheme['bg'] }} p-4.5 transition duration-200 hover:-translate-y-0.5 hover:shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-bold {{ $catTheme['badge'] }}">
                                <x-icon name="project" class="h-3.5 w-3.5" />
                                {{ $stat['name'] }}
                            </span>
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                {{ $stat['rate'] }}%
                            </span>
                        </div>

                        <div class="mt-4 flex items-baseline justify-between text-xs text-slate-500 dark:text-slate-400">
                            <span>{{ $stat['completed'] }} of {{ $stat['total'] }} completed</span>
                            <span>{{ $stat['in_progress'] }} active</span>
                        </div>

                        <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-slate-200/70 dark:bg-slate-700">
                            <div class="h-full rounded-full {{ $catTheme['bar'] }} transition-all duration-300" style="width: {{ $stat['rate'] }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="col-span-full py-8 text-center text-sm text-slate-400">No project categories yet.</p>
                @endforelse
            </div>
        </section>

        {{-- =========================================================
            AI PRODUCTIVITY COACH BANNER
        ========================================================== --}}
        <section class="relative overflow-hidden rounded-3xl border border-indigo-200/80 bg-gradient-to-r from-slate-900 via-indigo-950 to-blue-950 p-6 sm:p-8 text-white shadow-xl dark:border-indigo-900">
            <div class="pointer-events-none absolute -bottom-10 -right-10 h-64 w-64 rounded-full bg-indigo-500/20 blur-3xl"></div>

            <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-start gap-4">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-indigo-500/30 text-indigo-300 ring-1 ring-indigo-400/40">
                        <x-icon name="sparkles" class="h-6 w-6" />
                    </span>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-lg font-black text-white">AI Productivity Copilot</h3>
                            <span class="rounded-full bg-emerald-500/20 px-2 py-0.5 text-[10px] font-bold text-emerald-300 ring-1 ring-emerald-400/30">Active Analysis</span>
                        </div>
                        <p class="mt-1 max-w-2xl text-xs sm:text-sm text-slate-300">
                            @if ($overdueCount > 0)
                                You have {{ $overdueCount }} overdue task{{ $overdueCount > 1 ? 's' : '' }}. Consider reviewing your high priority backlog or rescheduling deadlines to maintain a healthy workflow.
                            @elseif ($completionRate >= 70)
                                Outstanding momentum! You have finished {{ $completionRate }}% of your total workload. Focus on completing in-progress items today.
                            @else
                                Keep consistent focus on your {{ $highCount }} high priority tasks to accelerate your completion score.
                            @endif
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button type="button" onclick="document.getElementById('ai-chatbot-launcher-btn')?.click()"
                        class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-500 to-blue-600 px-5 text-xs font-bold text-white shadow-md transition hover:scale-102 hover:from-indigo-600 hover:to-blue-700">
                        <x-icon name="bot" class="h-4 w-4" />
                        <span>Chat with Copilot</span>
                    </button>
                </div>
            </div>
        </section>

    </div>
@endsection
