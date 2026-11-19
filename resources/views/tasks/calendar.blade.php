@extends('layouts.app')

@section('title', 'Calendar & Schedule - WorkMind')

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

        $categoryColors = [
            'Work' => 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/50 dark:text-indigo-300 dark:border-indigo-800',
            'Personal' => 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/50 dark:text-purple-300 dark:border-purple-800',
            'Urgent' => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/50 dark:text-rose-300 dark:border-rose-800',
            'Design' => 'bg-pink-50 text-pink-700 border-pink-200 dark:bg-pink-950/50 dark:text-pink-300 dark:border-pink-800',
            'Dev' => 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-950/50 dark:text-sky-300 dark:border-sky-800',
            'Study' => 'bg-teal-50 text-teal-700 border-teal-200 dark:bg-teal-950/50 dark:text-teal-300 dark:border-teal-800',
            'Finance' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-800',
        ];

        $scheduledCount = $groupedTasks->sum(fn($tasks) => $tasks->count());
        $unscheduledCount = $unscheduledTasks->count();
        $totalCount = $allTasks->count();

        $todayDateStr = now()->toDateString();
        $todayCount = isset($groupedTasks[$todayDateStr]) ? $groupedTasks[$todayDateStr]->count() : 0;

        // Overdue calculation
        $overdueTasks = $allTasks->filter(function ($t) {
            $dueDate = $t->due_date ?? $t->end_date;
            return $dueDate && $dueDate->isPast() && !$dueDate->isToday() && $t->status !== 'completed';
        });
        $overdueCount = $overdueTasks->count();

        // Month grid calculations
        $monthStart = $calendarMonth->copy()->startOfMonth();
        $monthEnd = $calendarMonth->copy()->endOfMonth();
        $startDayOfWeek = ($monthStart->dayOfWeekIso - 1); // 0 = Mon, 6 = Sun
        $daysInMonth = $calendarMonth->daysInMonth;
        $prevMonth = $calendarMonth->copy()->subMonth();
        $nextMonth = $calendarMonth->copy()->addMonth();
        $prevMonthDays = $prevMonth->daysInMonth;

        $totalCellsNeeded = (int) ceil(($startDayOfWeek + $daysInMonth) / 7) * 7;
        $trailingDaysCount = $totalCellsNeeded - ($startDayOfWeek + $daysInMonth);

        // Unique categories for filtering
        $allCategories = $allTasks->pluck('category')->filter()->unique()->values();
    @endphp

    {{-- JSON payload for interactive client-side operations --}}
    <script id="calendar-tasks-data" type="application/json">
        {!! json_encode($allTasks->map(function ($task) {
            $dueDate = $task->due_date ?? $task->end_date;
            return [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'priority' => $task->priority,
                'status' => $task->status,
                'category' => $task->category,
                'due_date' => $dueDate ? $dueDate->format('Y-m-d') : null,
                'end_date' => $task->end_date ? $task->end_date->format('Y-m-d') : null,
                'is_pinned' => (bool) $task->is_pinned,
                'subtasks' => $task->subtasks ?? [],
                'subtasks_count' => $task->subtasks_count,
                'completed_subtasks_count' => $task->completed_subtasks_count,
                'subtasks_progress' => $task->subtasks_progress,
                'is_overdue' => $task->is_overdue,
                'show_url' => route('tasks.show', $task),
                'edit_url' => route('tasks.edit', $task),
                'toggle_url' => route('tasks.toggle-status', $task),
                'quick_update_url' => route('tasks.quick-update', $task),
            ];
        })) !!}
    </script>

    <div class="mx-auto max-w-[1600px] space-y-6 pb-12" id="calendar-app" data-current-month="{{ $calendarMonth->format('Y-m') }}" data-today="{{ $todayDateStr }}">

        {{-- =========================================================
            DESKTOP / MOBILE MODE SWITCHER PILL (VISIBLE ON DESKTOP)
        ========================================================== --}}
        <div class="hidden md:flex items-center justify-between pb-1">
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Device Experience</span>
            </div>
            <div class="flex items-center rounded-2xl border border-slate-200 bg-white p-1 shadow-2xs dark:border-slate-800 dark:bg-slate-900">
                <button type="button" id="switch-desktop-view-btn"
                    class="device-switch-btn flex items-center gap-1.5 rounded-xl px-3.5 py-1.5 text-xs font-bold transition bg-blue-50 text-blue-600 dark:bg-blue-950 dark:text-blue-400">
                    <x-icon name="grid" class="h-3.5 w-3.5" />
                    <span>Desktop Mode</span>
                </button>
                <button type="button" id="switch-iphone-view-btn"
                    class="device-switch-btn flex items-center gap-1.5 rounded-xl px-3.5 py-1.5 text-xs font-bold transition text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="4"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                    <span>iPhone App Mode</span>
                </button>
            </div>
        </div>


        {{-- ====================================================================
            ====================================================================
            1. IPHONE / MOBILE CALENDAR EXPERIENCE
            ====================================================================
            ==================================================================== --}}
        <div id="iphone-experience-wrapper" class="block md:hidden">

            {{-- Optional iPhone Frame Container (Active when toggled on desktop) --}}
            <div id="iphone-mockup-frame" class="mx-auto max-w-[420px] transition-all duration-300">
                <div class="relative overflow-hidden rounded-[40px] border-[8px] border-slate-900 bg-[#f2f2f7] shadow-2xl dark:border-slate-800 dark:bg-slate-950 min-h-[760px] flex flex-col justify-between">

                    {{-- iOS Dynamic Island & Status Bar --}}
                    <div class="relative px-6 pt-3 pb-2 flex items-center justify-between text-slate-900 dark:text-white select-none z-30">
                        <span class="text-xs font-bold tracking-tight">9:41</span>
                        {{-- Dynamic Island --}}
                        <div class="h-6 w-24 rounded-full bg-black flex items-center justify-end pr-2">
                            <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        </div>
                        <div class="flex items-center gap-1.5 text-xs">
                            {{-- Cellular --}}
                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 3c-4.97 0-9 4.03-9 9 0 2.12.74 4.07 1.97 5.61L4.35 19.4c-.39.39-.39 1.02 0 1.41.39.39 1.02.39 1.41 0l1.9-1.9C9.22 19.58 10.57 20 12 20c4.97 0 9-4.03 9-9s-4.03-9-9-9z"/></svg>
                            {{-- Wi-Fi --}}
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/></svg>
                            {{-- Battery --}}
                            <div class="h-3 w-5 rounded-[4px] border border-current p-0.5 flex items-center">
                                <div class="h-full w-full rounded-[2px] bg-current"></div>
                            </div>
                        </div>
                    </div>

                    {{-- iOS App Top Bar --}}
                    <div class="px-5 py-2.5 bg-[#f2f2f7]/80 dark:bg-slate-950/80 backdrop-blur-xl border-b border-slate-200/60 dark:border-slate-800/60 sticky top-0 z-20">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-1.5">
                                <h2 class="text-xl font-extrabold text-slate-950 dark:text-white tracking-tight">
                                    {{ $calendarMonth->format('F') }}
                                </h2>
                                <span class="text-sm font-semibold text-slate-400">{{ $calendarMonth->format('Y') }}</span>
                            </div>

                            <div class="flex items-center gap-2">
                                {{-- iOS Today Pill Button --}}
                                <button type="button" id="ios-today-btn"
                                    class="h-8 rounded-full bg-white px-3 text-xs font-bold text-blue-600 shadow-2xs hover:bg-slate-50 dark:bg-slate-900 dark:text-blue-400 dark:hover:bg-slate-850 active:scale-95 transition">
                                    Today
                                </button>

                                {{-- Search toggle --}}
                                <button type="button" id="ios-search-toggle"
                                    class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-slate-700 shadow-2xs hover:bg-slate-50 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-850 active:scale-95 transition">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                </button>

                                {{-- iOS + Quick Add --}}
                                <button type="button" data-open-task-modal
                                    class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-600 text-white shadow-md shadow-blue-500/25 active:scale-95 transition">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                </button>
                            </div>
                        </div>

                        {{-- iOS Search Input (Collapsible) --}}
                        <div id="ios-search-container" class="hidden mt-2 pt-1">
                            <input type="text" id="ios-search-input" placeholder="Search tasks..."
                                class="h-9 w-full rounded-xl bg-slate-200/70 px-3.5 text-xs font-medium text-slate-900 outline-none placeholder:text-slate-400 dark:bg-slate-850 dark:text-white">
                        </div>

                        {{-- iOS Cupertino Segmented Control --}}
                        <div class="mt-3 flex rounded-xl bg-slate-200/80 p-0.5 dark:bg-slate-850">
                            <button type="button" data-ios-view="day"
                                class="ios-tab-btn flex-1 rounded-lg py-1.5 text-xs font-bold text-slate-900 shadow-xs bg-white dark:bg-slate-700 dark:text-white transition">
                                Day
                            </button>
                            <button type="button" data-ios-view="month"
                                class="ios-tab-btn flex-1 rounded-lg py-1.5 text-xs font-semibold text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition">
                                Month
                            </button>
                            <button type="button" data-ios-view="agenda"
                                class="ios-tab-btn flex-1 rounded-lg py-1.5 text-xs font-semibold text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition">
                                Agenda
                            </button>
                        </div>
                    </div>

                    {{-- Apple Calendar Horizontal Week Strip --}}
                    <div class="bg-white px-3 py-2.5 dark:bg-slate-900 border-b border-slate-200/60 dark:border-slate-800/60 shadow-xs">
                        {{-- Weekday letters M T W T F S S --}}
                        <div class="grid grid-cols-7 text-center text-[11px] font-bold text-slate-400 mb-1">
                            <span>M</span>
                            <span>T</span>
                            <span>W</span>
                            <span>T</span>
                            <span>F</span>
                            <span class="text-rose-500">S</span>
                            <span class="text-rose-500">S</span>
                        </div>

                        {{-- 7-Day Apple Strip Numbers --}}
                        <div class="grid grid-cols-7 gap-1" id="ios-week-strip-days">
                            {{-- Populated dynamically with circular badges and priority dots --}}
                        </div>

                        {{-- Expand/Collapse Chevron Pull --}}
                        <div class="mt-1 flex justify-center">
                            <button type="button" id="ios-toggle-month-expand-btn" class="p-1 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition">
                                <svg id="ios-expand-chevron" class="h-3.5 w-3.5 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                        </div>

                        {{-- Expanded iOS Month Grid (Slides down on demand) --}}
                        <div id="ios-expanded-month-grid" class="hidden mt-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                            <div class="grid grid-cols-7 gap-1 text-center" id="ios-full-month-cells">
                                {{-- Injected dynamically --}}
                            </div>
                        </div>
                    </div>

                    {{-- iOS Main Content / Schedule Feed --}}
                    <div class="flex-1 overflow-y-auto px-4 py-3 space-y-3" id="ios-tasks-feed">
                        {{-- Day Header --}}
                        <div class="flex items-center justify-between pt-1">
                            <div>
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-blue-600 dark:text-blue-400" id="ios-feed-sublabel">SCHEDULE</span>
                                <h3 class="text-base font-extrabold text-slate-900 dark:text-white" id="ios-feed-date-title">Today</h3>
                            </div>
                            <span class="rounded-full bg-blue-100 dark:bg-blue-950 px-2 py-0.5 text-[10px] font-bold text-blue-700 dark:text-blue-300" id="ios-feed-task-count">
                                0 Tasks
                            </span>
                        </div>

                        {{-- iOS Cards Container --}}
                        <div class="space-y-2.5 pb-20" id="ios-feed-cards-list">
                            {{-- Injected dynamically by JavaScript --}}
                        </div>
                    </div>

                    {{-- iOS Bottom Floating Action Button (FAB) & Home Indicator --}}
                    <div class="relative pointer-events-none p-4 flex flex-col items-center">
                        <button type="button" data-open-task-modal
                            class="pointer-events-auto absolute right-5 -top-16 flex h-14 w-14 items-center justify-center rounded-full bg-blue-600 text-white shadow-xl shadow-blue-500/35 active:scale-95 transition">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        </button>

                        {{-- iOS Home Indicator Bar --}}
                        <div class="h-1 w-32 rounded-full bg-slate-950/30 dark:bg-white/30 mx-auto mt-2"></div>
                    </div>

                </div>
            </div>

        </div>


        {{-- ====================================================================
            ====================================================================
            2. DESKTOP WORKSPACE (FULL MODERN DESKTOP EXPERIENCE)
            ====================================================================
            ==================================================================== --}}
        <div id="desktop-experience-wrapper" class="hidden md:block space-y-6">

            {{-- =========================================================
                HERO HEADER & NAVIGATION CONTROLS
            ========================================================== --}}
            <section class="relative overflow-hidden rounded-3xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900 transition-colors duration-200">
                <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-blue-100/50 blur-3xl dark:bg-blue-900/20"></div>
                <div class="pointer-events-none absolute right-52 top-10 h-36 w-36 rounded-full bg-indigo-100/50 blur-3xl dark:bg-indigo-900/20"></div>

                <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <div class="mb-3 flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-700 dark:bg-blue-950/70 dark:text-blue-400">
                                <span class="h-2 w-2 rounded-full bg-blue-500 animate-ping"></span>
                                <span data-i18n="calendar">Interactive Schedule</span>
                            </span>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                {{ $calendarMonth->format('F Y') }}
                            </span>
                            @if ($overdueCount > 0)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-700 dark:bg-rose-950/60 dark:text-rose-400 ring-1 ring-rose-500/20 animate-pulse">
                                    <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                    <span>{{ $overdueCount }} Overdue</span>
                                </span>
                            @endif
                        </div>

                        <h1 class="text-3xl font-extrabold tracking-tight text-slate-950 sm:text-4xl dark:text-white" data-i18n="calendar">
                            Calendar & Schedule
                        </h1>

                        <p class="mt-2 max-w-xl text-sm leading-6 text-slate-500 sm:text-base dark:text-slate-400" data-i18n="next_deadlines">
                            Plan workload, visualize deadlines, and drag & drop tasks across your calendar timeline.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        {{-- Month Navigator --}}
                        <div class="relative flex items-center rounded-2xl border border-slate-200 bg-white p-1 shadow-xs dark:border-slate-700 dark:bg-slate-800">
                            <a href="{{ route('calendar', ['month' => $prevMonth->format('Y-m')]) }}"
                               class="flex h-10 w-10 items-center justify-center rounded-xl text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-700 transition"
                               title="Previous Month (Alt + ←)">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                            </a>

                            {{-- Month picker dropdown trigger --}}
                            <button type="button" id="month-picker-toggle"
                                    class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-extrabold text-slate-800 hover:text-blue-600 dark:text-slate-200 dark:hover:text-blue-400 transition rounded-lg hover:bg-slate-50 dark:hover:bg-slate-750">
                                <span>{{ $calendarMonth->format('M Y') }}</span>
                                <svg class="h-3 w-3 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </button>

                            <a href="{{ route('calendar', ['month' => $nextMonth->format('Y-m')]) }}"
                               class="flex h-10 w-10 items-center justify-center rounded-xl text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-700 transition"
                               title="Next Month (Alt + →)">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                            </a>

                            {{-- Month / Year Quick Jump Popover --}}
                            <div id="month-picker-popover" class="hidden absolute top-full left-0 mt-2 z-40 w-64 rounded-2xl border border-slate-200 bg-white p-4 shadow-xl dark:border-slate-700 dark:bg-slate-850">
                                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                                    <span class="text-xs font-bold text-slate-400">Select Month & Year</span>
                                    <div class="flex items-center gap-1">
                                        <button type="button" id="picker-prev-year" class="p-1 text-slate-400 hover:text-slate-700 dark:hover:text-white rounded">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                                        </button>
                                        <span id="picker-year-label" class="text-xs font-bold text-slate-900 dark:text-white px-1">{{ $calendarMonth->format('Y') }}</span>
                                        <button type="button" id="picker-next-year" class="p-1 text-slate-400 hover:text-slate-700 dark:hover:text-white rounded">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="mt-3 grid grid-cols-3 gap-1.5 text-center text-xs">
                                    @foreach (['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'] as $mIndex => $mName)
                                        @php $mPad = str_pad($mIndex + 1, 2, '0', STR_PAD_LEFT); @endphp
                                        <button type="button" data-picker-month="{{ $mPad }}"
                                            class="picker-month-btn rounded-xl py-2 font-semibold transition {{ $calendarMonth->format('m') === $mPad ? 'bg-blue-600 text-white font-bold shadow-xs' : 'text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                                            {{ $mName }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        {{-- Today Jump Button --}}
                        <button type="button" id="jump-today-btn"
                            class="inline-flex h-11 items-center justify-center gap-1.5 rounded-2xl border border-slate-200 bg-white px-4 text-xs font-bold text-slate-700 shadow-xs transition hover:bg-slate-50 hover:text-blue-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-750 dark:hover:text-blue-400"
                            title="Jump to Today (Key: T)">
                            <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                            <span>Today</span>
                        </button>

                        {{-- View Mode Switcher (Month / Week / Agenda) --}}
                        <div class="flex items-center rounded-2xl border border-slate-200 bg-slate-50/80 p-1 dark:border-slate-700 dark:bg-slate-800">
                            <button type="button" data-calendar-view="month"
                                class="cal-view-btn flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-bold transition bg-white text-blue-600 shadow-xs dark:bg-slate-900 dark:text-blue-400"
                                title="Month View (Key: M)">
                                <x-icon name="grid" class="h-3.5 w-3.5" />
                                <span>Month</span>
                            </button>
                            <button type="button" data-calendar-view="week"
                                class="cal-view-btn flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-bold transition text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white"
                                title="Week View (Key: W)">
                                <x-icon name="columns" class="h-3.5 w-3.5" />
                                <span>Week</span>
                            </button>
                            <button type="button" data-calendar-view="agenda"
                                class="cal-view-btn flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-bold transition text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white"
                                title="Agenda View (Key: A)">
                                <x-icon name="list" class="h-3.5 w-3.5" />
                                <span>Agenda</span>
                            </button>
                        </div>

                        {{-- Kanban shortcut --}}
                        <a href="{{ route('tasks.index') }}"
                            class="inline-flex h-11 items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 text-xs font-bold text-slate-700 shadow-xs transition hover:bg-slate-50 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                            <x-icon name="kanban" class="h-4 w-4" />
                            <span data-i18n="my_tasks">Kanban</span>
                        </a>

                        {{-- Add Task Modal Trigger --}}
                        <button type="button" data-open-task-modal id="header-add-task-btn"
                            class="inline-flex h-11 items-center justify-center gap-2 rounded-2xl bg-blue-600 px-5 text-xs font-bold text-white shadow-md shadow-blue-600/20 transition duration-200 hover:-translate-y-0.5 hover:bg-blue-700 active:translate-y-0">
                            <x-icon name="plus" class="h-4 w-4" />
                            <span data-i18n="new_task">Add Task</span>
                        </button>
                    </div>
                </div>

                {{-- Metric Summary Badges --}}
                <div class="mt-6 pt-6 border-t border-slate-100 dark:border-slate-800 grid grid-cols-2 gap-3 sm:grid-cols-5">
                    <button type="button" data-filter-status="all" class="metric-card text-left rounded-2xl bg-slate-50/70 p-3.5 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800/60 hover:border-slate-300 dark:hover:border-slate-700 transition">
                        <span class="text-xs font-medium text-slate-400">Total Tasks</span>
                        <p class="mt-1 text-2xl font-black text-slate-900 dark:text-white">{{ $totalCount }}</p>
                    </button>
                    <div class="metric-card rounded-2xl bg-blue-50/70 p-3.5 dark:bg-blue-950/30 border border-blue-100/60 dark:border-blue-900/40">
                        <span class="text-xs font-medium text-blue-600 dark:text-blue-400">Scheduled in {{ $calendarMonth->format('M') }}</span>
                        <p class="mt-1 text-2xl font-black text-blue-700 dark:text-blue-300">{{ $scheduledCount }}</p>
                    </div>
                    <button type="button" id="stat-due-today-btn" class="metric-card text-left rounded-2xl bg-emerald-50/70 p-3.5 dark:bg-emerald-950/30 border border-emerald-100/60 dark:border-emerald-900/40 hover:ring-2 hover:ring-emerald-400/40 transition">
                        <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400">Due Today</span>
                        <p class="mt-1 text-2xl font-black text-emerald-700 dark:text-emerald-300">{{ $todayCount }}</p>
                    </button>
                    <button type="button" id="stat-overdue-btn" class="metric-card text-left rounded-2xl {{ $overdueCount > 0 ? 'bg-rose-50/80 dark:bg-rose-950/40 border-rose-200 dark:border-rose-900/50 hover:ring-2 hover:ring-rose-400/40' : 'bg-slate-50/70 dark:bg-slate-800/40 border-slate-100 dark:border-slate-800/60' }} p-3.5 border transition">
                        <span class="text-xs font-medium {{ $overdueCount > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-400' }}">Overdue Tasks</span>
                        <p class="mt-1 text-2xl font-black {{ $overdueCount > 0 ? 'text-rose-600 dark:text-rose-300' : 'text-slate-700 dark:text-slate-400' }}">{{ $overdueCount }}</p>
                    </button>
                    <button type="button" id="stat-backlog-btn" class="metric-card text-left col-span-2 sm:col-span-1 rounded-2xl bg-amber-50/70 p-3.5 dark:bg-amber-950/30 border border-amber-100/60 dark:border-amber-900/40 hover:ring-2 hover:ring-amber-400/40 transition">
                        <span class="text-xs font-medium text-amber-600 dark:text-amber-400">Unscheduled</span>
                        <p class="mt-1 text-2xl font-black text-amber-700 dark:text-amber-300">{{ $unscheduledCount }}</p>
                    </button>
                </div>
            </section>

            {{-- =========================================================
                FILTER & SEARCH TOOLBAR
            ========================================================== --}}
            <section class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900 transition-colors">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    {{-- Live Search input --}}
                    <div class="relative flex-1 max-w-md">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        </div>
                        <input type="text" id="calendar-search-input"
                            placeholder="Search tasks on calendar by title or tag..."
                            class="h-10 w-full rounded-xl border border-slate-200 bg-slate-50/60 pl-10 pr-4 text-xs font-medium text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:focus:ring-blue-900/40">
                    </div>

                    {{-- Filter Selectors --}}
                    <div class="flex flex-wrap items-center gap-2">
                        {{-- Priority filter --}}
                        <select id="filter-priority-select" class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 outline-none transition hover:border-slate-300 focus:border-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                            <option value="">Priority: All</option>
                            <option value="high">🔴 High Priority</option>
                            <option value="medium">🟡 Medium Priority</option>
                            <option value="low">🟢 Low Priority</option>
                        </select>

                        {{-- Status filter --}}
                        <select id="filter-status-select" class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 outline-none transition hover:border-slate-300 focus:border-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                            <option value="">Status: All</option>
                            <option value="pending">⏳ Pending</option>
                            <option value="in_progress">🔄 In Progress</option>
                            <option value="completed">✅ Completed</option>
                        </select>

                        {{-- Category filter --}}
                        @if ($allCategories->isNotEmpty())
                            <select id="filter-category-select" class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 outline-none transition hover:border-slate-300 focus:border-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                <option value="">Category: All</option>
                                @foreach ($allCategories as $cat)
                                    <option value="{{ $cat }}">{{ $cat }}</option>
                                @endforeach
                            </select>
                        @endif

                        {{-- Reset filter button --}}
                        <button type="button" id="clear-filters-btn" class="hidden h-10 items-center gap-1.5 rounded-xl border border-rose-200 bg-rose-50 px-3 text-xs font-bold text-rose-700 hover:bg-rose-100 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-300 transition">
                            <x-icon name="x" class="h-3 w-3" />
                            <span>Clear</span>
                        </button>
                    </div>
                </div>
            </section>

            {{-- =========================================================
                MAIN CALENDAR WORKSPACE (GRID / WEEK / AGENDA + SIDE PANEL)
            ========================================================== --}}
            <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">

                <div class="space-y-6">

                    {{-- VIEW 1: MONTH GRID VIEW --}}
                    <section id="month-view-container" class="cal-view-panel rounded-3xl border border-slate-200/80 bg-white p-5 sm:p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 transition-colors duration-200">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2.5">
                                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950 dark:text-blue-400">
                                    <x-icon name="calendar" class="h-4 w-4" />
                                </span>
                                <h2 class="text-base font-bold text-slate-900 dark:text-white">
                                    {{ $calendarMonth->format('F Y') }}
                                </h2>
                            </div>
                            <div class="flex items-center gap-2 text-xs text-slate-400">
                                <span>Tip: Drag tasks to reschedule · Click cell to inspect</span>
                            </div>
                        </div>

                        {{-- Day of Week Headers --}}
                        <div class="grid grid-cols-7 gap-1.5 text-center text-xs font-extrabold uppercase tracking-wider text-slate-400 mb-2">
                            <div class="py-1">Mon</div>
                            <div class="py-1">Tue</div>
                            <div class="py-1">Wed</div>
                            <div class="py-1">Thu</div>
                            <div class="py-1">Fri</div>
                            <div class="py-1 text-indigo-600 dark:text-indigo-400">Sat</div>
                            <div class="py-1 text-indigo-600 dark:text-indigo-400">Sun</div>
                        </div>

                        {{-- Calendar Month Grid Cells --}}
                        <div class="grid grid-cols-7 gap-1.5 sm:gap-2" id="month-cells-grid">
                            {{-- 1. Leading days from previous month --}}
                            @for ($i = $startDayOfWeek; $i > 0; $i--)
                                @php
                                    $prevDayNum = $prevMonthDays - $i + 1;
                                    $prevDateObj = $prevMonth->copy()->day($prevDayNum);
                                    $prevDateStr = $prevDateObj->toDateString();
                                @endphp
                                <div data-calendar-date="{{ $prevDateStr }}"
                                     class="month-cell group min-h-[92px] sm:min-h-[120px] rounded-2xl border border-slate-100/60 bg-slate-50/30 p-2 opacity-50 dark:border-slate-800/40 dark:bg-slate-850/20 transition hover:opacity-80">
                                    <span class="text-xs font-semibold text-slate-400">{{ $prevDayNum }}</span>
                                </div>
                            @endfor

                            {{-- 2. Days of current month --}}
                            @for ($day = 1; $day <= $daysInMonth; $day++)
                                @php
                                    $dayDate = $calendarMonth->copy()->day($day);
                                    $dateStr = $dayDate->toDateString();
                                    $isToday = $dayDate->isToday();
                                    $dayTasks = $groupedTasks->get($dateStr, collect());
                                    $hasTasks = $dayTasks->isNotEmpty();
                                    $isWeekend = in_array($dayDate->dayOfWeekIso, [6, 7]);
                                @endphp

                                <div data-calendar-date="{{ $dateStr }}"
                                     class="month-cell group relative min-h-[92px] sm:min-h-[120px] rounded-2xl border p-2 sm:p-2.5 transition-all duration-200 flex flex-col justify-between cursor-pointer
                                            {{ $isToday ? 'border-blue-500 bg-blue-50/40 shadow-xs dark:bg-blue-950/30 dark:border-blue-500' : 'border-slate-100 bg-white hover:border-slate-300 hover:shadow-xs dark:border-slate-800/80 dark:bg-slate-850/60 dark:hover:border-slate-700' }}
                                            {{ $isWeekend && !$isToday ? 'bg-slate-50/30 dark:bg-slate-850/30' : '' }}"
                                     title="Click to inspect agenda for {{ $dayDate->format('M d') }}">

                                    {{-- Cell Header: Day number + Add button + Counter --}}
                                    <div class="flex items-center justify-between pointer-events-auto">
                                        <span class="day-number flex h-6 w-6 items-center justify-center rounded-lg text-xs font-extrabold transition
                                                     {{ $isToday ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-700 group-hover:text-slate-900 dark:text-slate-300 dark:group-hover:text-white' }}">
                                            {{ $day }}
                                        </span>

                                        <div class="flex items-center gap-1">
                                            {{-- Quick Add Task for this day --}}
                                            <button type="button"
                                                    data-add-task-date="{{ $dateStr }}"
                                                    class="opacity-0 group-hover:opacity-100 flex h-6 w-6 items-center justify-center rounded-lg bg-slate-100 text-slate-600 hover:bg-blue-600 hover:text-white dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-blue-600 transition"
                                                    title="Create task for {{ $dayDate->format('M d, Y') }}">
                                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                                            </button>

                                            @if ($hasTasks)
                                                <span class="rounded-full bg-blue-100 px-1.5 py-0.5 text-[10px] font-bold text-blue-700 dark:bg-blue-900/80 dark:text-blue-300">
                                                    {{ $dayTasks->count() }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Task Chips Container --}}
                                    <div class="mt-1.5 space-y-1 flex-1 overflow-hidden" data-day-tasks-container="{{ $dateStr }}">
                                        @foreach ($dayTasks->take(3) as $task)
                                            <div draggable="true"
                                                 data-task-id="{{ $task->id }}"
                                                 data-task-priority="{{ $task->priority }}"
                                                 data-task-status="{{ $task->status }}"
                                                 data-task-category="{{ $task->category }}"
                                                 class="task-chip hidden sm:flex items-center gap-1.5 truncate rounded-lg px-2 py-1 text-[11px] font-semibold border transition duration-150 cursor-grab active:cursor-grabbing hover:shadow-xs
                                                        {{ $task->status === 'completed' ? 'border-emerald-200 bg-emerald-50 text-emerald-700 line-through dark:border-emerald-900/40 dark:bg-emerald-950/40 dark:text-emerald-400' : 'border-slate-200/70 bg-slate-50 text-slate-800 hover:border-slate-300 dark:border-slate-750 dark:bg-slate-800 dark:text-slate-200' }}"
                                                 title="{{ $task->title }} ({{ ucfirst($task->priority) }} Priority)">
                                                <span class="h-1.5 w-1.5 rounded-full shrink-0 {{ $task->priority === 'high' ? 'bg-rose-500' : ($task->priority === 'medium' ? 'bg-amber-500' : 'bg-emerald-500') }}"></span>
                                                <span class="truncate">{{ $task->title }}</span>
                                            </div>
                                        @endforeach

                                        @if ($dayTasks->count() > 3)
                                            <div class="hidden sm:block text-[10px] font-bold text-blue-600 dark:text-blue-400 px-1 hover:underline">
                                                +{{ $dayTasks->count() - 3 }} more
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endfor

                            {{-- 3. Trailing days from next month --}}
                            @for ($j = 1; $j <= $trailingDaysCount; $j++)
                                @php
                                    $nextDateObj = $nextMonth->copy()->day($j);
                                    $nextDateStr = $nextDateObj->toDateString();
                                @endphp
                                <div data-calendar-date="{{ $nextDateStr }}"
                                     class="month-cell group min-h-[92px] sm:min-h-[120px] rounded-2xl border border-slate-100/60 bg-slate-50/30 p-2 opacity-50 dark:border-slate-800/40 dark:bg-slate-850/20 transition hover:opacity-80">
                                    <span class="text-xs font-semibold text-slate-400">{{ $j }}</span>
                                </div>
                            @endfor
                        </div>
                    </section>


                    {{-- VIEW 2: WEEK SCHEDULE BOARD VIEW --}}
                    <section id="week-view-container" class="cal-view-panel hidden rounded-3xl border border-slate-200/80 bg-white p-5 sm:p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 transition-colors duration-200">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
                            <div class="flex items-center gap-3">
                                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950 dark:text-blue-400">
                                    <x-icon name="columns" class="h-4 w-4" />
                                </span>
                                <div>
                                    <h2 class="text-base font-bold text-slate-900 dark:text-white" id="week-view-range-label">
                                        Week Schedule
                                    </h2>
                                    <p class="text-xs text-slate-400">7-day workload distribution & drag to reorganize</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <button type="button" id="week-prev-btn" class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                                </button>
                                <button type="button" id="week-today-btn" class="px-3 py-1.5 text-xs font-bold rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                    This Week
                                </button>
                                <button type="button" id="week-next-btn" class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                                </button>
                            </div>
                        </div>

                        {{-- 7 Week Columns --}}
                        <div class="grid grid-cols-1 md:grid-cols-7 gap-3" id="week-columns-grid">
                            {{-- Injected dynamically by JavaScript --}}
                        </div>
                    </section>


                    {{-- VIEW 3: AGENDA TIMELINE VIEW --}}
                    <section id="agenda-view-container" class="cal-view-panel hidden overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 transition-colors duration-200">
                        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5 dark:border-slate-800">
                            <div class="flex items-center gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400">
                                    <x-icon name="list" class="h-5 w-5" />
                                </span>
                                <div>
                                    <h2 class="font-bold text-slate-950 dark:text-white">Agenda Timeline</h2>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">All scheduled tasks ordered chronologically</p>
                                </div>
                            </div>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300" id="agenda-total-counter">
                                {{ $scheduledCount }} Tasks
                            </span>
                        </div>

                        <div class="divide-y divide-slate-100 dark:divide-slate-800" id="agenda-groups-list">
                            @forelse ($groupedTasks as $date => $tasks)
                                @php
                                    $calendarDate = \Carbon\Carbon::parse($date);
                                    $isToday = $calendarDate->isToday();
                                    $isPast = $calendarDate->isPast() && !$calendarDate->isToday();
                                    $dateTone = $isToday
                                        ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20'
                                        : ($isPast ? 'bg-rose-500 text-white shadow-md shadow-rose-500/20' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300');
                                @endphp

                                <div id="date-{{ $date }}" data-agenda-date="{{ $date }}" class="agenda-date-group scroll-mt-24 p-6 transition hover:bg-slate-50/40 dark:hover:bg-slate-850/40">
                                    <div class="mb-4 flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-12 w-12 shrink-0 flex-col items-center justify-center rounded-2xl {{ $dateTone }}">
                                                <span class="text-[9px] font-bold uppercase tracking-wider">{{ $calendarDate->format('M') }}</span>
                                                <span class="text-base font-extrabold leading-none">{{ $calendarDate->format('d') }}</span>
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <h3 class="font-bold text-slate-900 dark:text-white">{{ $calendarDate->format('l') }}</h3>
                                                    @if ($isToday)
                                                        <span class="rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-bold text-blue-700 dark:bg-blue-950 dark:text-blue-300">Today</span>
                                                    @elseif ($isPast)
                                                        <span class="rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-bold text-rose-700 dark:bg-rose-950 dark:text-rose-400">Overdue</span>
                                                    @endif
                                                </div>
                                                <p class="text-xs text-slate-400">{{ $calendarDate->format('F d, Y') }} · {{ $tasks->count() }} {{ Str::plural('task', $tasks->count()) }}</p>
                                            </div>
                                        </div>

                                        <button type="button" data-add-task-date="{{ $date }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-blue-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 transition">
                                            <x-icon name="plus" class="h-3.5 w-3.5" />
                                            <span>Add</span>
                                        </button>
                                    </div>

                                    <div class="space-y-3">
                                        @foreach ($tasks as $task)
                                            <div data-agenda-task-id="{{ $task->id }}"
                                                 class="agenda-task-card group relative flex flex-col gap-3 rounded-2xl border border-slate-200/70 bg-white p-4 transition-all duration-200 hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md sm:flex-row sm:items-center sm:justify-between dark:border-slate-800 dark:bg-slate-850 dark:hover:border-slate-700">
                                                <div class="flex items-start gap-3 min-w-0">
                                                    <button type="button"
                                                            data-toggle-task-id="{{ $task->id }}"
                                                            class="toggle-status-btn mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-lg border transition {{ $task->status === 'completed' ? 'bg-emerald-500 border-emerald-500 text-white' : 'border-slate-300 dark:border-slate-700 text-transparent hover:text-slate-400 hover:border-slate-400' }}">
                                                        <x-icon name="check" class="h-3.5 w-3.5" />
                                                    </button>

                                                    <div class="min-w-0 flex-1">
                                                        <div class="flex flex-wrap items-center gap-2">
                                                            <button type="button" data-preview-task-id="{{ $task->id }}"
                                                               class="task-title-btn text-left font-bold text-sm text-slate-900 dark:text-white hover:text-blue-600 transition {{ $task->status === 'completed' ? 'line-through text-slate-400 dark:text-slate-500' : '' }}">
                                                                {{ $task->title }}
                                                            </button>

                                                            @if ($task->priority === 'high')
                                                                <span class="rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-bold text-rose-600 dark:bg-rose-950/60 dark:text-rose-400 ring-1 ring-rose-500/20">High</span>
                                                            @elseif ($task->priority === 'medium')
                                                                <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-600 dark:bg-amber-950/60 dark:text-amber-400 ring-1 ring-amber-500/20">Medium</span>
                                                            @endif

                                                            @if ($task->category)
                                                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600 dark:bg-slate-700 dark:text-slate-300">
                                                                    {{ $task->category }}
                                                                </span>
                                                            @endif

                                                            @if ($task->subtasks_count > 0)
                                                                <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                                                                    <x-icon name="check-circle" class="h-3 w-3" />
                                                                    <span>{{ $task->completed_subtasks_count }}/{{ $task->subtasks_count }}</span>
                                                                </span>
                                                            @endif
                                                        </div>

                                                        @if ($task->description)
                                                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 line-clamp-1">
                                                                {{ $task->description }}
                                                            </p>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="flex items-center gap-2 shrink-0 self-end sm:self-center">
                                                    <button type="button"
                                                            data-preview-task-id="{{ $task->id }}"
                                                            class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200 transition"
                                                            title="Quick Preview">
                                                        <x-icon name="eye" class="h-4 w-4" />
                                                    </button>
                                                    <a href="{{ route('tasks.edit', $task) }}"
                                                       data-edit-task-id="{{ $task->id }}"
                                                       class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200 transition"
                                                       title="Edit Task">
                                                        <x-icon name="edit" class="h-4 w-4" />
                                                    </a>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @empty
                                <div class="p-12 text-center">
                                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-950 dark:text-blue-400">
                                        <x-icon name="calendar" class="h-6 w-6" />
                                    </span>
                                    <h3 class="mt-4 text-base font-bold text-slate-900 dark:text-white">No scheduled tasks yet</h3>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Add due dates to your tasks to see them organized on the calendar timeline.</p>
                                    <button type="button" data-open-task-modal class="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-blue-700 transition">
                                        <x-icon name="plus" class="h-3.5 w-3.5" />
                                        <span>Create First Task</span>
                                    </button>
                                </div>
                            @endforelse
                        </div>
                    </section>

                </div>


                {{-- =========================================================
                    RIGHT PANEL: DAY INSPECTOR & UNSCHEDULED BACKLOG
                ========================================================== --}}
                <aside class="space-y-6">

                    <div class="sticky top-6 rounded-3xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 transition-colors duration-200 overflow-hidden">
                        {{-- Tab Switcher --}}
                        <div class="flex items-center border-b border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-850/60 p-1.5">
                            <button type="button" id="tab-btn-inspector"
                                class="flex-1 rounded-xl py-2 px-3 text-xs font-bold transition bg-white text-blue-600 shadow-xs dark:bg-slate-900 dark:text-blue-400 flex items-center justify-center gap-1.5">
                                <x-icon name="calendar" class="h-3.5 w-3.5" />
                                <span>Day Inspector</span>
                            </button>
                            <button type="button" id="tab-btn-backlog"
                                class="flex-1 rounded-xl py-2 px-3 text-xs font-bold transition text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white flex items-center justify-center gap-1.5">
                                <x-icon name="inbox" class="h-3.5 w-3.5" />
                                <span>Backlog ({{ $unscheduledCount }})</span>
                            </button>
                        </div>

                        {{-- TAB 1: DAY INSPECTOR CONTENT --}}
                        <div id="inspector-panel" class="p-6 space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-blue-600 dark:text-blue-400" id="inspector-badge-label">Selected Date</span>
                                    <h3 class="text-base font-extrabold text-slate-900 dark:text-white" id="inspector-date-heading">Today</h3>
                                    <p class="text-xs text-slate-400" id="inspector-date-sub">{{ now()->format('l, F d, Y') }}</p>
                                </div>

                                <button type="button" id="inspector-add-btn"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white dark:bg-blue-950 dark:text-blue-400 dark:hover:bg-blue-600 dark:hover:text-white transition"
                                    title="Add task for this day">
                                    <x-icon name="plus" class="h-4 w-4" />
                                </button>
                            </div>

                            {{-- Day completion progress bar --}}
                            <div class="rounded-2xl bg-slate-50 p-3.5 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800">
                                <div class="flex items-center justify-between text-xs font-semibold mb-2">
                                    <span class="text-slate-500 dark:text-slate-400">Day Progress</span>
                                    <span id="inspector-progress-text" class="text-slate-800 dark:text-slate-200">0%</span>
                                </div>
                                <div class="h-2 w-full rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden">
                                    <div id="inspector-progress-bar" class="h-full rounded-full bg-blue-600 transition-all duration-300" style="width: 0%"></div>
                                </div>
                            </div>

                            {{-- Tasks list for selected day --}}
                            <div class="space-y-2.5 max-h-[460px] overflow-y-auto pr-1" id="inspector-tasks-list">
                                {{-- Populated dynamically by JavaScript --}}
                            </div>
                        </div>

                        {{-- TAB 2: UNSCHEDULED BACKLOG CONTENT --}}
                        <div id="backlog-panel" class="hidden p-6 space-y-4">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                                <div>
                                    <h3 class="font-bold text-slate-900 dark:text-white">Unscheduled Tasks</h3>
                                    <p class="text-xs text-slate-400">Drag to calendar or click date to schedule</p>
                                </div>
                                <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700 dark:bg-amber-950 dark:text-amber-400">
                                    {{ $unscheduledCount }}
                                </span>
                            </div>

                            <div class="space-y-3 max-h-[500px] overflow-y-auto pr-1" id="backlog-tasks-list">
                                @forelse ($unscheduledTasks as $task)
                                    <div draggable="true"
                                         data-task-id="{{ $task->id }}"
                                         data-backlog-item
                                         class="group rounded-2xl border border-slate-200/80 bg-slate-50/70 p-3.5 transition hover:border-slate-300 hover:bg-white hover:shadow-xs dark:border-slate-800 dark:bg-slate-850/80 dark:hover:border-slate-700 dark:hover:bg-slate-800 cursor-grab active:cursor-grabbing">
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="min-w-0 flex-1">
                                                <button type="button" data-preview-task-id="{{ $task->id }}"
                                                    class="text-left text-xs font-bold text-slate-900 dark:text-slate-100 hover:text-blue-600 transition truncate block w-full">
                                                    {{ $task->title }}
                                                </button>
                                                @if ($task->description)
                                                    <p class="mt-0.5 text-[11px] text-slate-400 line-clamp-1">{{ $task->description }}</p>
                                                @endif
                                            </div>
                                            <span class="h-2 w-2 rounded-full shrink-0 {{ $task->priority === 'high' ? 'bg-rose-500' : ($task->priority === 'medium' ? 'bg-amber-500' : 'bg-emerald-500') }}" title="{{ ucfirst($task->priority) }} Priority"></span>
                                        </div>

                                        <div class="mt-3 flex items-center justify-between text-[11px] pt-2 border-t border-slate-200/60 dark:border-slate-800">
                                            <span class="rounded bg-slate-200/60 px-1.5 py-0.5 font-medium text-slate-600 dark:bg-slate-700 dark:text-slate-300">
                                                {{ $task->category ?: 'General' }}
                                            </span>

                                            <div class="flex items-center gap-1.5">
                                                <button type="button"
                                                    data-schedule-today="{{ $task->id }}"
                                                    class="font-bold text-blue-600 dark:text-blue-400 hover:underline">
                                                    + Due Today
                                                </button>
                                                <a href="{{ route('tasks.edit', $task) }}" data-edit-task-id="{{ $task->id }}" class="text-slate-400 hover:text-slate-700 dark:hover:text-white p-1 rounded">
                                                    <x-icon name="edit" class="h-3 w-3" />
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="py-12 text-center">
                                        <span class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400">
                                            <x-icon name="check-circle" class="h-5 w-5" />
                                        </span>
                                        <p class="mt-3 text-xs font-bold text-slate-700 dark:text-slate-300">Backlog is empty!</p>
                                        <p class="mt-1 text-[11px] text-slate-400">All of your tasks have scheduled deadlines.</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                </aside>

            </div>

        </div>

    </div>

    {{-- =========================================================
        TASK QUICK-PREVIEW & BOTTOM SHEET (IOS + DESKTOP)
    ========================================================== --}}
    <div id="task-preview-drawer" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
        <div id="drawer-backdrop" class="fixed inset-0 bg-slate-950/40 backdrop-blur-xs transition-opacity"></div>
        <div class="fixed inset-x-0 bottom-0 md:inset-y-0 md:right-0 md:left-auto md:max-w-md w-full flex">
            <div class="w-full bg-white dark:bg-slate-900 rounded-t-[32px] md:rounded-t-none md:border-l border-slate-200 dark:border-slate-800 shadow-2xl flex flex-col justify-between max-h-[85vh] md:max-h-full">

                {{-- iOS Grab Handle (Mobile only) --}}
                <div class="md:hidden pt-3 pb-1 flex justify-center">
                    <div class="h-1.5 w-12 rounded-full bg-slate-300 dark:bg-slate-700"></div>
                </div>

                {{-- Drawer Header --}}
                <div class="p-5 md:p-6 border-b border-slate-100 dark:border-slate-800 flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-1.5">
                            <span id="drawer-priority-badge" class="rounded-full px-2 py-0.5 text-[10px] font-bold">Priority</span>
                            <span id="drawer-category-badge" class="rounded-full px-2 py-0.5 text-[10px] font-semibold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">Category</span>
                        </div>
                        <h3 id="drawer-title" class="text-lg font-extrabold text-slate-900 dark:text-white leading-snug">Task Title</h3>
                    </div>
                    <button type="button" id="close-drawer-btn" class="p-2 text-slate-400 hover:text-slate-700 dark:hover:text-white rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                        <x-icon name="x" class="h-5 w-5" />
                    </button>
                </div>

                {{-- Drawer Body --}}
                <div class="p-5 md:p-6 overflow-y-auto space-y-5 flex-1">
                    {{-- Status Switcher Buttons --}}
                    <div>
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-2">Status</label>
                        <div class="grid grid-cols-3 gap-2" id="drawer-status-group">
                            <button type="button" data-set-status="pending" class="status-btn py-2 text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800">
                                Pending
                            </button>
                            <button type="button" data-set-status="in_progress" class="status-btn py-2 text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800">
                                In Progress
                            </button>
                            <button type="button" data-set-status="completed" class="status-btn py-2 text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800">
                                Completed
                            </button>
                        </div>
                    </div>

                    {{-- Schedule Date Info & Quick Edit --}}
                    <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950 dark:text-blue-400">
                                <x-icon name="calendar" class="h-4 w-4" />
                            </span>
                            <div>
                                <span class="text-[10px] font-bold text-slate-400 uppercase">Deadline</span>
                                <p id="drawer-due-date" class="text-xs font-bold text-slate-900 dark:text-white">Not set</p>
                            </div>
                        </div>
                        <input type="date" id="drawer-quick-date-picker" class="text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-2.5 py-1.5 font-semibold text-slate-800 dark:text-slate-200 outline-none">
                    </div>

                    {{-- Description --}}
                    <div>
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-2">Description</label>
                        <div id="drawer-description" class="text-xs text-slate-600 dark:text-slate-300 whitespace-pre-wrap leading-relaxed rounded-2xl bg-slate-50/60 p-4 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800 min-h-[60px]">
                            No description provided.
                        </div>
                    </div>

                    {{-- Subtasks Checklist --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Subtasks Checklist</label>
                            <span id="drawer-subtasks-count" class="text-xs font-bold text-slate-500">0/0</span>
                        </div>
                        <div id="drawer-subtasks-list" class="space-y-2">
                            {{-- Populated dynamically --}}
                        </div>
                    </div>
                </div>

                {{-- Drawer Footer Actions --}}
                <div class="p-5 md:p-6 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-3 bg-slate-50/50 dark:bg-slate-850/50">
                    <a id="drawer-full-details-link" href="#" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-600 hover:text-blue-600 dark:text-slate-300 dark:hover:text-blue-400">
                        <span>Full Details</span>
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                    </a>

                    <div class="flex items-center gap-2">
                        <button type="button" id="drawer-edit-btn" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 shadow-xs hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                            <x-icon name="edit" class="h-3.5 w-3.5" />
                            <span>Edit</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>


    {{-- =========================================================
        CLIENT-SIDE INTERACTIVE CALENDAR ENGINE
    ========================================================== --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const rawTasksData = document.getElementById('calendar-tasks-data')?.textContent || '[]';
            let tasks = [];
            try {
                tasks = JSON.parse(rawTasksData);
            } catch (e) {
                console.error('Failed to parse calendar tasks data:', e);
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const currentMonthStr = document.getElementById('calendar-app')?.dataset.currentMonth || '';
            const todayStr = document.getElementById('calendar-app')?.dataset.today || '';
            let selectedDateStr = todayStr;
            let currentView = 'month'; // 'month', 'week', 'agenda'
            let iosView = 'day'; // 'day', 'month', 'agenda'
            let isIosMonthExpanded = false;

            // Filters
            let searchQuery = '';
            let filterPriority = '';
            let filterStatus = '';
            let filterCategory = '';

            // Week state
            let currentWeekStartDate = getMondayOfWeek(new Date());

            // -------------------------------------------------------------
            // Helper: Date operations
            // -------------------------------------------------------------
            function getMondayOfWeek(date) {
                const d = new Date(date);
                const day = d.getDay();
                const diff = d.getDate() - day + (day === 0 ? -6 : 1);
                return new Date(d.setDate(diff));
            }

            function formatDateISO(d) {
                const year = d.getFullYear();
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }

            function formatHumanDate(dateStr) {
                if (!dateStr) return '';
                const parts = dateStr.split('-');
                const d = new Date(parts[0], parts[1] - 1, parts[2]);
                return d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
            }

            // -------------------------------------------------------------
            // Filter Evaluation
            // -------------------------------------------------------------
            function taskMatchesFilters(task) {
                if (searchQuery) {
                    const q = searchQuery.toLowerCase();
                    const titleMatch = (task.title || '').toLowerCase().includes(q);
                    const descMatch = (task.description || '').toLowerCase().includes(q);
                    const catMatch = (task.category || '').toLowerCase().includes(q);
                    if (!titleMatch && !descMatch && !catMatch) return false;
                }
                if (filterPriority && task.priority !== filterPriority) return false;
                if (filterStatus && task.status !== filterStatus) return false;
                if (filterCategory && task.category !== filterCategory) return false;
                return true;
            }

            // -------------------------------------------------------------
            // Drag and Drop Rescheduling Engine (Desktop)
            // -------------------------------------------------------------
            let draggedTaskId = null;

            function setupDragAndDrop() {
                document.querySelectorAll('[draggable="true"]').forEach(el => {
                    el.addEventListener('dragstart', (e) => {
                        draggedTaskId = el.dataset.taskId;
                        e.dataTransfer.setData('text/plain', draggedTaskId);
                        e.dataTransfer.effectAllowed = 'move';
                        el.classList.add('opacity-40');
                    });
                    el.addEventListener('dragend', () => {
                        el.classList.remove('opacity-40');
                        draggedTaskId = null;
                    });
                });

                document.querySelectorAll('[data-calendar-date]').forEach(cell => {
                    cell.addEventListener('dragover', (e) => {
                        e.preventDefault();
                        e.dataTransfer.dropEffect = 'move';
                        cell.classList.add('ring-2', 'ring-blue-500', 'bg-blue-100/40', 'dark:bg-blue-900/40');
                    });

                    cell.addEventListener('dragleave', () => {
                        cell.classList.remove('ring-2', 'ring-blue-500', 'bg-blue-100/40', 'dark:bg-blue-900/40');
                    });

                    cell.addEventListener('drop', (e) => {
                        e.preventDefault();
                        cell.classList.remove('ring-2', 'ring-blue-500', 'bg-blue-100/40', 'dark:bg-blue-900/40');
                        const taskId = e.dataTransfer.getData('text/plain') || draggedTaskId;
                        const targetDate = cell.dataset.calendarDate;
                        if (taskId && targetDate) {
                            rescheduleTask(taskId, targetDate);
                        }
                    });
                });
            }

            async function rescheduleTask(taskId, targetDate) {
                const task = tasks.find(t => String(t.id) === String(taskId));
                if (!task) return;

                const previousDate = task.due_date;
                task.due_date = targetDate;
                task.end_date = targetDate;

                // Optimistic UI updates
                renderDayInspector(selectedDateStr);
                renderWeekView();
                updateMonthGridChips();
                renderIosView();

                try {
                    const response = await fetch(task.quick_update_url, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({
                            due_date: targetDate,
                            end_date: targetDate,
                        }),
                    });

                    if (response.ok) {
                        if (window.showToast) {
                            window.showToast('success', `"${task.title}" scheduled for ${targetDate}`);
                        }
                    } else {
                        // Rollback on failure
                        task.due_date = previousDate;
                        task.end_date = previousDate;
                        renderDayInspector(selectedDateStr);
                        renderWeekView();
                        updateMonthGridChips();
                        renderIosView();
                        if (window.showToast) {
                            window.showToast('error', 'Could not update due date');
                        }
                    }
                } catch (err) {
                    console.error('Reschedule failed:', err);
                    task.due_date = previousDate;
                    task.end_date = previousDate;
                    renderDayInspector(selectedDateStr);
                    renderWeekView();
                    updateMonthGridChips();
                    renderIosView();
                }
            }

            // -------------------------------------------------------------
            // Month View Live Filter & Highlight (Desktop)
            // -------------------------------------------------------------
            function updateMonthGridChips() {
                document.querySelectorAll('.month-cell[data-calendar-date]').forEach(cell => {
                    const cellDate = cell.dataset.calendarDate;
                    const container = cell.querySelector('[data-day-tasks-container]');
                    if (!container) return;

                    const dayTasks = tasks.filter(t => t.due_date === cellDate && taskMatchesFilters(t));
                    container.innerHTML = '';

                    dayTasks.slice(0, 3).forEach(task => {
                        const chip = document.createElement('div');
                        chip.setAttribute('draggable', 'true');
                        chip.dataset.taskId = task.id;
                        chip.className = `task-chip hidden sm:flex items-center gap-1.5 truncate rounded-lg px-2 py-1 text-[11px] font-semibold border transition duration-150 cursor-grab active:cursor-grabbing hover:shadow-xs ${
                            task.status === 'completed'
                                ? 'border-emerald-200 bg-emerald-50 text-emerald-700 line-through dark:border-emerald-900/40 dark:bg-emerald-950/40 dark:text-emerald-400'
                                : 'border-slate-200/70 bg-slate-50 text-slate-800 hover:border-slate-300 dark:border-slate-750 dark:bg-slate-800 dark:text-slate-200'
                        }`;
                        chip.title = `${task.title} (${task.priority} Priority)`;

                        const dotColor = task.priority === 'high' ? 'bg-rose-500' : (task.priority === 'medium' ? 'bg-amber-500' : 'bg-emerald-500');
                        chip.innerHTML = `
                            <span class="h-1.5 w-1.5 rounded-full shrink-0 ${dotColor}"></span>
                            <span class="truncate">${escapeHtml(task.title)}</span>
                        `;
                        chip.addEventListener('click', (e) => {
                            e.stopPropagation();
                            openTaskPreview(task.id);
                        });
                        container.appendChild(chip);
                    });

                    if (dayTasks.length > 3) {
                        const moreBtn = document.createElement('div');
                        moreBtn.className = 'hidden sm:block text-[10px] font-bold text-blue-600 dark:text-blue-400 px-1 hover:underline cursor-pointer';
                        moreBtn.textContent = `+${dayTasks.length - 3} more`;
                        moreBtn.addEventListener('click', (e) => {
                            e.stopPropagation();
                            selectCalendarDate(cellDate);
                        });
                        container.appendChild(moreBtn);
                    }
                });

                setupDragAndDrop();
            }

            // -------------------------------------------------------------
            // Day Selection & Day Inspector (Desktop)
            // -------------------------------------------------------------
            function selectCalendarDate(dateStr) {
                selectedDateStr = dateStr;

                // Visual cell highlight in desktop month grid
                document.querySelectorAll('.month-cell').forEach(cell => {
                    const isSelected = cell.dataset.calendarDate === dateStr;
                    cell.classList.toggle('ring-2', isSelected);
                    cell.classList.toggle('ring-blue-600', isSelected);
                    cell.classList.toggle('shadow-md', isSelected);
                });

                renderDayInspector(dateStr);
                renderIosView();
            }

            function renderDayInspector(dateStr) {
                const heading = document.getElementById('inspector-date-heading');
                const sub = document.getElementById('inspector-date-sub');
                const badge = document.getElementById('inspector-badge-label');
                const list = document.getElementById('inspector-tasks-list');
                const pText = document.getElementById('inspector-progress-text');
                const pBar = document.getElementById('inspector-progress-bar');
                if (!heading || !list) return;

                const isToday = dateStr === todayStr;
                heading.textContent = isToday ? 'Today' : formatHumanDate(dateStr);
                if (sub) sub.textContent = formatHumanDate(dateStr);
                if (badge) badge.textContent = isToday ? 'Current Day' : 'Selected Date';

                const dayTasks = tasks.filter(t => t.due_date === dateStr);
                const total = dayTasks.length;
                const completed = dayTasks.filter(t => t.status === 'completed').length;
                const progressPct = total > 0 ? Math.round((completed / total) * 100) : 0;

                if (pText) pText.textContent = `${completed}/${total} completed (${progressPct}%)`;
                if (pBar) pBar.style.width = `${progressPct}%`;

                list.innerHTML = '';
                if (dayTasks.length === 0) {
                    list.innerHTML = `
                        <div class="py-10 text-center">
                            <span class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </span>
                            <p class="mt-3 text-xs font-bold text-slate-700 dark:text-slate-300">No tasks for this day</p>
                            <p class="mt-1 text-[11px] text-slate-400">Enjoy some focus time or add a new task.</p>
                            <button type="button" data-add-task-date="${dateStr}" class="mt-3 inline-flex items-center gap-1.5 rounded-xl bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-600 hover:bg-blue-100 dark:bg-blue-950 dark:text-blue-300 transition">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                                <span>Schedule Task</span>
                            </button>
                        </div>
                    `;
                    return;
                }

                dayTasks.forEach(task => {
                    const card = document.createElement('div');
                    card.className = 'group rounded-2xl border border-slate-200/80 bg-white p-3.5 shadow-2xs transition hover:border-slate-300 dark:border-slate-800 dark:bg-slate-850 dark:hover:border-slate-700';

                    const isDone = task.status === 'completed';
                    const priorityColor = task.priority === 'high' ? 'bg-rose-50 text-rose-700 ring-rose-500/20' : (task.priority === 'medium' ? 'bg-amber-50 text-amber-700 ring-amber-500/20' : 'bg-emerald-50 text-emerald-700 ring-emerald-500/20');

                    card.innerHTML = `
                        <div class="flex items-start gap-3">
                            <button type="button" data-inspector-toggle="${task.id}"
                                class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-lg border transition ${isDone ? 'bg-emerald-500 border-emerald-500 text-white' : 'border-slate-300 dark:border-slate-700 text-transparent hover:border-slate-400'}">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            </button>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <button type="button" data-preview-task-id="${task.id}" class="text-left text-xs font-bold text-slate-900 dark:text-white hover:text-blue-600 transition truncate ${isDone ? 'line-through text-slate-400 dark:text-slate-500' : ''}">
                                        ${escapeHtml(task.title)}
                                    </button>
                                </div>
                                <div class="mt-1 flex items-center gap-2">
                                    <span class="rounded-full px-1.5 py-0.2 text-[10px] font-bold ring-1 ${priorityColor}">${task.priority}</span>
                                    ${task.category ? `<span class="rounded bg-slate-100 px-1.5 py-0.2 text-[10px] font-medium text-slate-600 dark:bg-slate-750 dark:text-slate-300">${escapeHtml(task.category)}</span>` : ''}
                                </div>
                            </div>
                            <div class="flex items-center gap-1">
                                <button type="button" data-preview-task-id="${task.id}" class="p-1 text-slate-400 hover:text-slate-700 dark:hover:text-white rounded" title="Quick view">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                                <a href="${task.edit_url}" data-edit-task-id="${task.id}" class="p-1 text-slate-400 hover:text-slate-700 dark:hover:text-white rounded" title="Edit">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                            </div>
                        </div>
                    `;
                    list.appendChild(card);
                });
            }

            // -------------------------------------------------------------
            // VIEW 2: WEEK VIEW RENDERER (Desktop)
            // -------------------------------------------------------------
            function renderWeekView() {
                const weekGrid = document.getElementById('week-columns-grid');
                const rangeLabel = document.getElementById('week-view-range-label');
                if (!weekGrid) return;

                const days = [];
                for (let i = 0; i < 7; i++) {
                    const d = new Date(currentWeekStartDate);
                    d.setDate(d.getDate() + i);
                    days.push(d);
                }

                if (rangeLabel) {
                    const startFmt = days[0].toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                    const endFmt = days[6].toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                    rangeLabel.textContent = `Week: ${startFmt} - ${endFmt}`;
                }

                weekGrid.innerHTML = '';
                days.forEach(dayDate => {
                    const dateStr = formatDateISO(dayDate);
                    const isToday = dateStr === todayStr;
                    const dayTasks = tasks.filter(t => t.due_date === dateStr && taskMatchesFilters(t));

                    const col = document.createElement('div');
                    col.setAttribute('data-calendar-date', dateStr);
                    col.className = `flex flex-col rounded-2xl border p-3 transition min-h-[400px] ${
                        isToday ? 'border-blue-500 bg-blue-50/30 dark:border-blue-500 dark:bg-blue-950/20' : 'border-slate-100 bg-slate-50/50 dark:border-slate-800 dark:bg-slate-850/50'
                    }`;

                    col.innerHTML = `
                        <div class="flex items-center justify-between pb-2.5 mb-2.5 border-b border-slate-200/60 dark:border-slate-800">
                            <div>
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block">${dayDate.toLocaleDateString('en-US', { weekday: 'short' })}</span>
                                <span class="text-sm font-extrabold ${isToday ? 'text-blue-600 dark:text-blue-400' : 'text-slate-800 dark:text-white'}">${dayDate.getDate()}</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <span class="rounded-full bg-slate-200/60 dark:bg-slate-700 px-1.5 py-0.2 text-[10px] font-bold text-slate-600 dark:text-slate-300">${dayTasks.length}</span>
                                <button type="button" data-add-task-date="${dateStr}" class="p-1 text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 rounded" title="Add task">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                                </button>
                            </div>
                        </div>

                        <div class="week-task-cards space-y-2 flex-1">
                            ${dayTasks.map(task => {
                                const isDone = task.status === 'completed';
                                const dotColor = task.priority === 'high' ? 'bg-rose-500' : (task.priority === 'medium' ? 'bg-amber-500' : 'bg-emerald-500');
                                return `
                                    <div draggable="true" data-task-id="${task.id}"
                                         class="rounded-xl border border-slate-200/80 bg-white p-2.5 shadow-2xs transition hover:border-slate-300 dark:border-slate-800 dark:bg-slate-850 cursor-grab active:cursor-grabbing">
                                        <div class="flex items-center justify-between gap-1 mb-1">
                                            <span class="h-2 w-2 rounded-full ${dotColor}"></span>
                                            <span class="text-[10px] text-slate-400">${escapeHtml(task.category || '')}</span>
                                        </div>
                                        <button type="button" data-preview-task-id="${task.id}" class="text-left text-xs font-bold text-slate-900 dark:text-white hover:text-blue-600 transition truncate block w-full ${isDone ? 'line-through text-slate-400' : ''}">
                                            ${escapeHtml(task.title)}
                                        </button>
                                        ${task.subtasks_count > 0 ? `
                                            <div class="mt-2 flex items-center justify-between text-[10px] text-slate-400">
                                                <span>Checklist</span>
                                                <span class="font-bold">${task.completed_subtasks_count}/${task.subtasks_count}</span>
                                            </div>
                                            <div class="h-1 w-full rounded-full bg-slate-100 dark:bg-slate-700 mt-1 overflow-hidden">
                                                <div class="h-full bg-blue-600 rounded-full" style="width: ${task.subtasks_progress}%"></div>
                                            </div>
                                        ` : ''}
                                    </div>
                                `;
                            }).join('')}
                            ${dayTasks.length === 0 ? `
                                <div class="h-full flex flex-col items-center justify-center p-3 text-center border border-dashed border-slate-200 dark:border-slate-800 rounded-xl opacity-60">
                                    <span class="text-[11px] text-slate-400">No tasks</span>
                                </div>
                            ` : ''}
                        </div>
                    `;
                    weekGrid.appendChild(col);
                });

                setupDragAndDrop();
            }

            // -------------------------------------------------------------
            // VIEW 3: AGENDA TIMELINE FILTER (Desktop)
            // -------------------------------------------------------------
            function filterAgendaView() {
                let totalVisible = 0;
                document.querySelectorAll('.agenda-date-group').forEach(group => {
                    const cards = group.querySelectorAll('.agenda-task-card');
                    let groupVisibleCount = 0;

                    cards.forEach(card => {
                        const taskId = card.dataset.agendaTaskId;
                        const task = tasks.find(t => String(t.id) === String(taskId));
                        if (!task || !taskMatchesFilters(task)) {
                            card.classList.add('hidden');
                        } else {
                            card.classList.remove('hidden');
                            groupVisibleCount++;
                        }
                    });

                    if (groupVisibleCount === 0) {
                        group.classList.add('hidden');
                    } else {
                        group.classList.remove('hidden');
                        totalVisible += groupVisibleCount;
                    }
                });

                const counter = document.getElementById('agenda-total-counter');
                if (counter) counter.textContent = `${totalVisible} Tasks`;
            }

            // -------------------------------------------------------------
            // =============================================================
            // 3. IPHONE / MOBILE INTERACTIVE ENGINE (AUTHENTIC APPLE HIG)
            // =============================================================
            // -------------------------------------------------------------
            function renderIosView() {
                renderIosWeekStrip();
                renderIosScheduleFeed();
            }

            function renderIosWeekStrip() {
                const stripContainer = document.getElementById('ios-week-strip-days');
                if (!stripContainer) return;

                const days = [];
                for (let i = 0; i < 7; i++) {
                    const d = new Date(currentWeekStartDate);
                    d.setDate(d.getDate() + i);
                    days.push(d);
                }

                stripContainer.innerHTML = '';
                days.forEach(dayDate => {
                    const dateStr = formatDateISO(dayDate);
                    const isToday = dateStr === todayStr;
                    const isSelected = dateStr === selectedDateStr;
                    const dayTasks = tasks.filter(t => t.due_date === dateStr);

                    const cell = document.createElement('button');
                    cell.type = 'button';
                    cell.className = 'flex flex-col items-center py-1 transition group';
                    cell.onclick = () => {
                        selectCalendarDate(dateStr);
                    };

                    // Number circle
                    let numClass = 'h-9 w-9 rounded-full flex items-center justify-center text-xs font-bold transition ';
                    if (isToday) {
                        numClass += 'bg-blue-600 text-white font-extrabold shadow-sm';
                    } else if (isSelected) {
                        numClass += 'bg-slate-900 text-white dark:bg-white dark:text-slate-900 font-extrabold shadow-xs';
                    } else {
                        numClass += 'text-slate-800 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800';
                    }

                    // Dots row
                    let dotsHtml = '';
                    if (dayTasks.length > 0) {
                        const topTasks = dayTasks.slice(0, 3);
                        dotsHtml = `<div class="h-2 flex items-center gap-0.5 justify-center mt-1">
                            ${topTasks.map(t => {
                                const dotColor = t.priority === 'high' ? 'bg-rose-500' : (t.priority === 'medium' ? 'bg-amber-500' : 'bg-emerald-500');
                                return `<span class="h-1 w-1 rounded-full ${dotColor}"></span>`;
                            }).join('')}
                        </div>`;
                    } else {
                        dotsHtml = '<div class="h-2 mt-1"></div>';
                    }

                    cell.innerHTML = `
                        <div class="${numClass}">${dayDate.getDate()}</div>
                        ${dotsHtml}
                    `;
                    stripContainer.appendChild(cell);
                });
            }

            function renderIosScheduleFeed() {
                const list = document.getElementById('ios-feed-cards-list');
                const title = document.getElementById('ios-feed-date-title');
                const countBadge = document.getElementById('ios-feed-task-count');
                const sublabel = document.getElementById('ios-feed-sublabel');
                if (!list) return;

                let feedTasks = [];
                if (iosView === 'day') {
                    feedTasks = tasks.filter(t => t.due_date === selectedDateStr && taskMatchesFilters(t));
                    const isToday = selectedDateStr === todayStr;
                    if (title) title.textContent = isToday ? 'Today' : formatHumanDate(selectedDateStr);
                    if (sublabel) sublabel.textContent = isToday ? 'SCHEDULE · TODAY' : 'SCHEDULE';
                } else if (iosView === 'agenda' || iosView === 'month') {
                    feedTasks = tasks.filter(t => taskMatchesFilters(t)).sort((a,b) => (a.due_date || '').localeCompare(b.due_date || ''));
                    if (title) title.textContent = 'Upcoming Timeline';
                    if (sublabel) sublabel.textContent = 'ALL SCHEDULED';
                }

                if (countBadge) countBadge.textContent = `${feedTasks.length} ${feedTasks.length === 1 ? 'Task' : 'Tasks'}`;

                list.innerHTML = '';
                if (feedTasks.length === 0) {
                    list.innerHTML = `
                        <div class="py-12 text-center rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800/60 p-6">
                            <div class="h-12 w-12 rounded-full bg-blue-50 dark:bg-blue-950 flex items-center justify-center mx-auto text-blue-600 dark:text-blue-400">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                            </div>
                            <h4 class="mt-3 text-sm font-bold text-slate-900 dark:text-white">No tasks scheduled</h4>
                            <p class="mt-1 text-xs text-slate-400">Nothing on your schedule for this day.</p>
                            <button type="button" data-add-task-date="${selectedDateStr}" class="mt-4 inline-flex items-center gap-1.5 rounded-full bg-blue-600 px-4 py-2 text-xs font-bold text-white shadow-md shadow-blue-500/20 active:scale-95 transition">
                                <span>+ Add Task</span>
                            </button>
                        </div>
                    `;
                    return;
                }

                feedTasks.forEach(task => {
                    const card = document.createElement('div');
                    const isDone = task.status === 'completed';

                    // iOS Left border color accent
                    let borderAccent = 'border-l-4 border-l-blue-500';
                    if (task.category === 'Work') borderAccent = 'border-l-4 border-l-indigo-500';
                    else if (task.category === 'Personal') borderAccent = 'border-l-4 border-l-purple-500';
                    else if (task.category === 'Urgent') borderAccent = 'border-l-4 border-l-rose-500';
                    else if (task.category === 'Dev') borderAccent = 'border-l-4 border-l-sky-500';

                    card.className = `rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800/60 p-3.5 shadow-2xs transition active:scale-[0.98] ${borderAccent}`;

                    card.innerHTML = `
                        <div class="flex items-start gap-3">
                            <button type="button" data-toggle-task-id="${task.id}"
                                class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border transition ${isDone ? 'bg-emerald-500 border-emerald-500 text-white' : 'border-slate-300 dark:border-slate-700 text-transparent'}">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </button>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-1">
                                    <button type="button" data-preview-task-id="${task.id}" class="text-left font-bold text-sm text-slate-900 dark:text-white truncate ${isDone ? 'line-through text-slate-400 dark:text-slate-500' : ''}">
                                        ${escapeHtml(task.title)}
                                    </button>
                                    <span class="h-2 w-2 rounded-full shrink-0 ${task.priority === 'high' ? 'bg-rose-500' : (task.priority === 'medium' ? 'bg-amber-500' : 'bg-emerald-500')}"></span>
                                </div>

                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                    <span class="rounded-md bg-slate-100 dark:bg-slate-800 px-2 py-0.5 text-[10px] font-semibold text-slate-600 dark:text-slate-300">
                                        ${escapeHtml(task.category || 'General')}
                                    </span>
                                    ${task.due_date ? `
                                        <span class="text-[10px] text-slate-400 flex items-center gap-1">
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            ${formatHumanDate(task.due_date)}
                                        </span>
                                    ` : ''}
                                    ${task.subtasks_count > 0 ? `
                                        <span class="text-[10px] text-slate-400 ml-auto font-medium">
                                            ✓ ${task.completed_subtasks_count}/${task.subtasks_count}
                                        </span>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                    list.appendChild(card);
                });
            }

            // iOS Tab Switcher
            document.querySelectorAll('[data-ios-view]').forEach(btn => {
                btn.addEventListener('click', () => {
                    iosView = btn.dataset.iosView;
                    document.querySelectorAll('[data-ios-view]').forEach(b => {
                        const isA = b.dataset.iosView === iosView;
                        b.classList.toggle('bg-white', isA);
                        b.classList.toggle('text-slate-900', isA);
                        b.classList.toggle('shadow-xs', isA);
                        b.classList.toggle('dark:bg-slate-700', isA);
                        b.classList.toggle('dark:text-white', isA);
                        b.classList.toggle('text-slate-500', !isA);
                    });
                    renderIosView();
                });
            });

            // iOS Today Jump
            document.getElementById('ios-today-btn')?.addEventListener('click', () => {
                currentWeekStartDate = getMondayOfWeek(new Date());
                selectCalendarDate(todayStr);
            });

            // iOS Search Bar Toggle
            document.getElementById('ios-search-toggle')?.addEventListener('click', () => {
                const s = document.getElementById('ios-search-container');
                s?.classList.toggle('hidden');
                if (!s?.classList.contains('hidden')) {
                    document.getElementById('ios-search-input')?.focus();
                }
            });

            document.getElementById('ios-search-input')?.addEventListener('input', (e) => {
                searchQuery = e.target.value.trim();
                renderIosScheduleFeed();
            });

            // iOS Expand / Collapse Month Grid Toggle
            const toggleMonthBtn = document.getElementById('ios-toggle-month-expand-btn');
            const expandedMonthGrid = document.getElementById('ios-expanded-month-grid');
            const expandChevron = document.getElementById('ios-expand-chevron');

            toggleMonthBtn?.addEventListener('click', () => {
                isIosMonthExpanded = !isIosMonthExpanded;
                expandedMonthGrid?.classList.toggle('hidden', !isIosMonthExpanded);
                if (expandChevron) {
                    expandChevron.style.transform = isIosMonthExpanded ? 'rotate(180deg)' : 'rotate(0deg)';
                }
                if (isIosMonthExpanded) {
                    renderIosFullMonthGrid();
                }
            });

            function renderIosFullMonthGrid() {
                const container = document.getElementById('ios-full-month-cells');
                if (!container) return;

                const [year, month] = currentMonthStr.split('-').map(Number);
                const firstDay = new Date(year, month - 1, 1);
                const daysInM = new Date(year, month, 0).getDate();
                const startDay = (firstDay.getDay() + 6) % 7; // Mon=0, Sun=6

                container.innerHTML = '';
                // Blanks
                for (let i = 0; i < startDay; i++) {
                    const empty = document.createElement('div');
                    empty.className = 'h-8 opacity-20 text-xs flex items-center justify-center';
                    container.appendChild(empty);
                }

                // Days
                for (let day = 1; day <= daysInM; day++) {
                    const d = new Date(year, month - 1, day);
                    const dStr = formatDateISO(d);
                    const isT = dStr === todayStr;
                    const isS = dStr === selectedDateStr;

                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = `h-8 w-8 rounded-full text-xs font-bold mx-auto flex items-center justify-center transition ${
                        isT ? 'bg-blue-600 text-white font-extrabold' : (isS ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900 font-extrabold' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800')
                    }`;
                    btn.textContent = day;
                    btn.onclick = () => {
                        selectCalendarDate(dStr);
                        // Update week start date to the selected day's week
                        currentWeekStartDate = getMondayOfWeek(d);
                        renderIosWeekStrip();
                    };
                    container.appendChild(btn);
                }
            }

            // Desktop Device Switcher Buttons (iPhone Mode vs Desktop Mode)
            const switchDesktopBtn = document.getElementById('switch-desktop-view-btn');
            const switchIphoneBtn = document.getElementById('switch-iphone-view-btn');
            const desktopWrapper = document.getElementById('desktop-experience-wrapper');
            const iphoneWrapper = document.getElementById('iphone-experience-wrapper');

            switchDesktopBtn?.addEventListener('click', () => {
                desktopWrapper?.classList.remove('hidden');
                iphoneWrapper?.classList.add('md:hidden');
                switchDesktopBtn.classList.add('bg-blue-50', 'text-blue-600', 'dark:bg-blue-950', 'dark:text-blue-400');
                switchDesktopBtn.classList.remove('text-slate-500');
                switchIphoneBtn?.classList.remove('bg-blue-50', 'text-blue-600', 'dark:bg-blue-950', 'dark:text-blue-400');
                switchIphoneBtn?.classList.add('text-slate-500');
            });

            switchIphoneBtn?.addEventListener('click', () => {
                desktopWrapper?.classList.add('hidden');
                iphoneWrapper?.classList.remove('md:hidden');
                switchIphoneBtn.classList.add('bg-blue-50', 'text-blue-600', 'dark:bg-blue-950', 'dark:text-blue-400');
                switchIphoneBtn.classList.remove('text-slate-500');
                switchDesktopBtn?.classList.remove('bg-blue-50', 'text-blue-600', 'dark:bg-blue-950', 'dark:text-blue-400');
                switchDesktopBtn?.classList.add('text-slate-500');
                renderIosView();
            });

            // -------------------------------------------------------------
            // TASK QUICK-PREVIEW DRAWER
            // -------------------------------------------------------------
            let activeDrawerTask = null;

            function openTaskPreview(taskId) {
                const task = tasks.find(t => String(t.id) === String(taskId));
                if (!task) return;

                activeDrawerTask = task;
                const drawer = document.getElementById('task-preview-drawer');
                if (!drawer) return;

                // Bind content
                document.getElementById('drawer-title').textContent = task.title;
                document.getElementById('drawer-category-badge').textContent = task.category || 'General';

                const pBadge = document.getElementById('drawer-priority-badge');
                if (pBadge) {
                    pBadge.textContent = (task.priority || 'medium').toUpperCase();
                    pBadge.className = `rounded-full px-2 py-0.5 text-[10px] font-bold ${
                        task.priority === 'high' ? 'bg-rose-50 text-rose-700 ring-1 ring-rose-500/20' : (task.priority === 'medium' ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-500/20' : 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-500/20')
                    }`;
                }

                // Status buttons styling
                document.querySelectorAll('#drawer-status-group .status-btn').forEach(btn => {
                    const s = btn.dataset.setStatus;
                    const isActive = s === task.status;
                    btn.classList.toggle('bg-blue-600', isActive);
                    btn.classList.toggle('text-white', isActive);
                    btn.classList.toggle('border-blue-600', isActive);
                });

                // Due date
                const dueText = document.getElementById('drawer-due-date');
                const datePicker = document.getElementById('drawer-quick-date-picker');
                if (dueText) dueText.textContent = task.due_date ? formatHumanDate(task.due_date) : 'No deadline set';
                if (datePicker) datePicker.value = task.due_date || '';

                // Description
                const desc = document.getElementById('drawer-description');
                if (desc) desc.textContent = task.description || 'No description provided for this task.';

                // Subtasks
                renderDrawerSubtasks(task);

                // Links
                const fullLink = document.getElementById('drawer-full-details-link');
                if (fullLink) fullLink.href = task.show_url;

                const editBtn = document.getElementById('drawer-edit-btn');
                if (editBtn) {
                    editBtn.onclick = () => {
                        closeTaskPreview();
                        const editTrigger = document.querySelector(`[data-edit-task-id="${task.id}"]`);
                        if (editTrigger) {
                            editTrigger.click();
                        } else if (window.openEditById) {
                            window.openEditById(task.id);
                        }
                    };
                }

                drawer.classList.remove('hidden');
            }

            function closeTaskPreview() {
                const drawer = document.getElementById('task-preview-drawer');
                if (drawer) drawer.classList.add('hidden');
                activeDrawerTask = null;
            }

            function renderDrawerSubtasks(task) {
                const list = document.getElementById('drawer-subtasks-list');
                const countBadge = document.getElementById('drawer-subtasks-count');
                if (!list) return;

                const subtasks = Array.isArray(task.subtasks) ? task.subtasks : [];
                const completedCount = subtasks.filter(st => st.completed).length;
                if (countBadge) countBadge.textContent = `${completedCount}/${subtasks.length}`;

                list.innerHTML = '';
                if (subtasks.length === 0) {
                    list.innerHTML = '<p class="text-xs text-slate-400 italic">No checklist subtasks on this task.</p>';
                    return;
                }

                subtasks.forEach((st, idx) => {
                    const item = document.createElement('label');
                    item.className = 'flex items-center gap-2.5 rounded-xl border border-slate-100 bg-slate-50/50 p-2.5 text-xs text-slate-700 dark:border-slate-800 dark:bg-slate-850 dark:text-slate-300 cursor-pointer hover:bg-slate-100/60 transition';
                    item.innerHTML = `
                        <input type="checkbox" data-drawer-subtask-idx="${idx}" ${st.completed ? 'checked' : ''} class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <span class="${st.completed ? 'line-through text-slate-400' : ''}">${escapeHtml(st.title || '')}</span>
                    `;
                    item.querySelector('input').addEventListener('change', async (e) => {
                        st.completed = e.target.checked;
                        renderDrawerSubtasks(task);
                        const subId = st.id || idx;
                        try {
                            const subToggleUrl = `/tasks/${task.id}/subtasks/${subId}/toggle`;
                            await fetch(subToggleUrl, {
                                method: 'PATCH',
                                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                            });
                        } catch (err) {
                            console.error('Subtask toggle error:', err);
                        }
                    });
                    list.appendChild(item);
                });
            }

            // -------------------------------------------------------------
            // Quick Status Toggle Handler
            // -------------------------------------------------------------
            async function toggleTaskStatus(taskId) {
                const task = tasks.find(t => String(t.id) === String(taskId));
                if (!task) return;

                const newStatus = task.status === 'completed' ? 'pending' : 'completed';
                task.status = newStatus;

                // Optimistic refresh
                updateMonthGridChips();
                renderWeekView();
                renderDayInspector(selectedDateStr);
                filterAgendaView();
                renderIosView();

                try {
                    const response = await fetch(task.toggle_url, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({ status: newStatus }),
                    });

                    if (response.ok && window.showToast) {
                        window.showToast('success', newStatus === 'completed' ? 'Task marked as completed! 🎉' : 'Task marked as pending');
                    }
                } catch (err) {
                    console.error('Toggle status failed:', err);
                }
            }

            // -------------------------------------------------------------
            // Event Listeners: Switch View Mode (Desktop)
            // -------------------------------------------------------------
            function setViewMode(view) {
                currentView = view;
                document.querySelectorAll('.cal-view-btn').forEach(btn => {
                    const isActive = btn.dataset.calendarView === view;
                    btn.classList.toggle('bg-white', isActive);
                    btn.classList.toggle('text-blue-600', isActive);
                    btn.classList.toggle('shadow-xs', isActive);
                    btn.classList.toggle('dark:bg-slate-900', isActive);
                    btn.classList.toggle('dark:text-blue-400', isActive);
                    btn.classList.toggle('text-slate-500', !isActive);
                });

                document.getElementById('month-view-container')?.classList.toggle('hidden', view !== 'month');
                document.getElementById('week-view-container')?.classList.toggle('hidden', view !== 'week');
                document.getElementById('agenda-view-container')?.classList.toggle('hidden', view !== 'agenda');

                if (view === 'week') renderWeekView();
                if (view === 'agenda') filterAgendaView();
                if (view === 'month') updateMonthGridChips();
            }

            document.querySelectorAll('[data-calendar-view]').forEach(btn => {
                btn.addEventListener('click', () => setViewMode(btn.dataset.calendarView));
            });

            // Month / Year Quick Jump Popover
            const monthPickerToggle = document.getElementById('month-picker-toggle');
            const monthPickerPopover = document.getElementById('month-picker-popover');
            let pickerYear = parseInt('{{ $calendarMonth->format("Y") }}', 10);

            monthPickerToggle?.addEventListener('click', (e) => {
                e.stopPropagation();
                monthPickerPopover?.classList.toggle('hidden');
            });

            document.getElementById('picker-prev-year')?.addEventListener('click', (e) => {
                e.stopPropagation();
                pickerYear--;
                document.getElementById('picker-year-label').textContent = pickerYear;
            });

            document.getElementById('picker-next-year')?.addEventListener('click', (e) => {
                e.stopPropagation();
                pickerYear++;
                document.getElementById('picker-year-label').textContent = pickerYear;
            });

            document.querySelectorAll('[data-picker-month]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const m = btn.dataset.pickerMonth;
                    window.location.href = `{{ route('calendar') }}?month=${pickerYear}-${m}`;
                });
            });

            document.addEventListener('click', (e) => {
                if (!monthPickerPopover?.contains(e.target) && e.target !== monthPickerToggle) {
                    monthPickerPopover?.classList.add('hidden');
                }
            });

            // Week Navigation
            document.getElementById('week-prev-btn')?.addEventListener('click', () => {
                currentWeekStartDate.setDate(currentWeekStartDate.getDate() - 7);
                renderWeekView();
            });
            document.getElementById('week-next-btn')?.addEventListener('click', () => {
                currentWeekStartDate.setDate(currentWeekStartDate.getDate() + 7);
                renderWeekView();
            });
            document.getElementById('week-today-btn')?.addEventListener('click', () => {
                currentWeekStartDate = getMondayOfWeek(new Date());
                renderWeekView();
            });

            // Jump to Today
            document.getElementById('jump-today-btn')?.addEventListener('click', () => {
                if (currentMonthStr !== todayStr.substring(0, 7)) {
                    window.location.href = '{{ route("calendar") }}';
                } else {
                    selectCalendarDate(todayStr);
                    const todayCell = document.querySelector(`[data-calendar-date="${todayStr}"]`);
                    todayCell?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });

            // Side Panel Tabs (Inspector vs Backlog)
            const tabInspector = document.getElementById('tab-btn-inspector');
            const tabBacklog = document.getElementById('tab-btn-backlog');
            const panelInspector = document.getElementById('inspector-panel');
            const panelBacklog = document.getElementById('backlog-panel');

            function switchSideTab(tab) {
                const isInspector = tab === 'inspector';
                tabInspector?.classList.toggle('bg-white', isInspector);
                tabInspector?.classList.toggle('text-blue-600', isInspector);
                tabInspector?.classList.toggle('shadow-xs', isInspector);
                tabInspector?.classList.toggle('dark:bg-slate-900', isInspector);
                tabInspector?.classList.toggle('dark:text-blue-400', isInspector);
                tabInspector?.classList.toggle('text-slate-500', !isInspector);

                tabBacklog?.classList.toggle('bg-white', !isInspector);
                tabBacklog?.classList.toggle('text-blue-600', !isInspector);
                tabBacklog?.classList.toggle('shadow-xs', !isInspector);
                tabBacklog?.classList.toggle('dark:bg-slate-900', !isInspector);
                tabBacklog?.classList.toggle('dark:text-blue-400', !isInspector);
                tabBacklog?.classList.toggle('text-slate-500', isInspector);

                panelInspector?.classList.toggle('hidden', !isInspector);
                panelBacklog?.classList.toggle('hidden', isInspector);
            }

            tabInspector?.addEventListener('click', () => switchSideTab('inspector'));
            tabBacklog?.addEventListener('click', () => switchSideTab('backlog'));

            document.getElementById('stat-backlog-btn')?.addEventListener('click', () => {
                switchSideTab('backlog');
            });
            document.getElementById('stat-due-today-btn')?.addEventListener('click', () => {
                selectCalendarDate(todayStr);
                switchSideTab('inspector');
            });
            document.getElementById('stat-overdue-btn')?.addEventListener('click', () => {
                setViewMode('agenda');
                const firstOverdue = document.querySelector('.agenda-date-group .bg-rose-500');
                firstOverdue?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });

            // Filters Input Listeners
            const searchInput = document.getElementById('calendar-search-input');
            const prioritySelect = document.getElementById('filter-priority-select');
            const statusSelect = document.getElementById('filter-status-select');
            const categorySelect = document.getElementById('filter-category-select');
            const clearFiltersBtn = document.getElementById('clear-filters-btn');

            function onFilterChange() {
                searchQuery = searchInput?.value.trim() || '';
                filterPriority = prioritySelect?.value || '';
                filterStatus = statusSelect?.value || '';
                filterCategory = categorySelect?.value || '';

                const hasActive = Boolean(searchQuery || filterPriority || filterStatus || filterCategory);
                clearFiltersBtn?.classList.toggle('hidden', !hasActive);
                clearFiltersBtn?.classList.toggle('inline-flex', hasActive);

                updateMonthGridChips();
                if (currentView === 'week') renderWeekView();
                if (currentView === 'agenda') filterAgendaView();
                renderIosView();
            }

            searchInput?.addEventListener('input', onFilterChange);
            prioritySelect?.addEventListener('change', onFilterChange);
            statusSelect?.addEventListener('change', onFilterChange);
            categorySelect?.addEventListener('change', onFilterChange);

            clearFiltersBtn?.addEventListener('click', () => {
                if (searchInput) searchInput.value = '';
                if (prioritySelect) prioritySelect.value = '';
                if (statusSelect) statusSelect.value = '';
                if (categorySelect) categorySelect.value = '';
                onFilterChange();
            });

            // Global Click Delegations
            document.addEventListener('click', (e) => {
                // 1. Add Task for specific date
                const addDateBtn = e.target.closest('[data-add-task-date]');
                if (addDateBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    const d = addDateBtn.dataset.addTaskDate;
                    if (window.openTaskModal) {
                        window.openTaskModal();
                        const dueInput = document.getElementById('create_due_date');
                        const endInput = document.getElementById('create_end_date');
                        if (dueInput) dueInput.value = d;
                        if (endInput) endInput.value = d;
                    }
                    return;
                }

                // 2. Click Day Cell in Month Grid
                const monthCell = e.target.closest('.month-cell');
                if (monthCell && !e.target.closest('.task-chip') && !e.target.closest('button')) {
                    const d = monthCell.dataset.calendarDate;
                    if (d) selectCalendarDate(d);
                    return;
                }

                // 3. Open Preview Drawer
                const previewBtn = e.target.closest('[data-preview-task-id]');
                if (previewBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    openTaskPreview(previewBtn.dataset.previewTaskId);
                    return;
                }

                // 4. Toggle Status button (Agenda or Inspector)
                const toggleBtn = e.target.closest('[data-toggle-task-id], [data-inspector-toggle]');
                if (toggleBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    const taskId = toggleBtn.dataset.toggleTaskId || toggleBtn.dataset.inspectorToggle;
                    toggleTaskStatus(taskId);
                    return;
                }

                // 5. Schedule Today from Backlog
                const schedTodayBtn = e.target.closest('[data-schedule-today]');
                if (schedTodayBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    rescheduleTask(schedTodayBtn.dataset.scheduleToday, todayStr);
                    return;
                }

                // 6. Close Drawer
                if (e.target.closest('#close-drawer-btn') || e.target === document.getElementById('drawer-backdrop')) {
                    closeTaskPreview();
                    return;
                }

                // 7. Drawer quick status switcher
                const drawerStatusBtn = e.target.closest('#drawer-status-group [data-set-status]');
                if (drawerStatusBtn && activeDrawerTask) {
                    const st = drawerStatusBtn.dataset.setStatus;
                    activeDrawerTask.status = st;
                    toggleTaskStatus(activeDrawerTask.id);
                    openTaskPreview(activeDrawerTask.id);
                    return;
                }

                // 8. Inspector Quick Add Task for currently selected day
                if (e.target.closest('#inspector-add-btn')) {
                    if (window.openTaskModal) {
                        window.openTaskModal();
                        const dueInput = document.getElementById('create_due_date');
                        const endInput = document.getElementById('create_end_date');
                        if (dueInput) dueInput.value = selectedDateStr;
                        if (endInput) endInput.value = selectedDateStr;
                    }
                    return;
                }
            });

            // Drawer Quick Date picker change
            document.getElementById('drawer-quick-date-picker')?.addEventListener('change', (e) => {
                if (activeDrawerTask && e.target.value) {
                    rescheduleTask(activeDrawerTask.id, e.target.value);
                    document.getElementById('drawer-due-date').textContent = formatHumanDate(e.target.value);
                }
            });

            // Keyboard Shortcuts
            document.addEventListener('keydown', (e) => {
                if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') {
                    if (e.key === 'Escape') {
                        closeTaskPreview();
                    }
                    return;
                }

                const key = e.key.toLowerCase();
                if (key === 't') {
                    e.preventDefault();
                    document.getElementById('jump-today-btn')?.click();
                } else if (key === 'm') {
                    e.preventDefault();
                    setViewMode('month');
                } else if (key === 'w') {
                    e.preventDefault();
                    setViewMode('week');
                } else if (key === 'a') {
                    e.preventDefault();
                    setViewMode('agenda');
                } else if (key === 'n') {
                    e.preventDefault();
                    window.openTaskModal?.();
                } else if (e.key === 'Escape') {
                    closeTaskPreview();
                    monthPickerPopover?.classList.add('hidden');
                }
            });

            function escapeHtml(str) {
                if (!str) return '';
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            // Initial render
            selectCalendarDate(todayStr);
            setupDragAndDrop();
            renderIosView();
        });
    </script>
@endsection
