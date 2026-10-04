@extends('layouts.app')

@section('title', 'Calendar & Schedule - Task Manager')

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

        $scheduledCount = $groupedTasks->sum(fn($tasks) => $tasks->count());
        $unscheduledCount = $unscheduledTasks->count();
        $totalCount = $scheduledCount + $unscheduledCount;

        $todayDateStr = now()->toDateString();
        $todayCount = isset($groupedTasks[$todayDateStr]) ? $groupedTasks[$todayDateStr]->count() : 0;

        $monthStart = $calendarMonth->copy()->startOfMonth();
        $monthEnd = $calendarMonth->copy()->endOfMonth();
        $startDayOfWeek = ($monthStart->dayOfWeekIso - 1); // 0 = Mon, 6 = Sun
        $daysInMonth = $calendarMonth->daysInMonth;
        $prevMonth = $calendarMonth->copy()->subMonth();
        $nextMonth = $calendarMonth->copy()->addMonth();
    @endphp

    <div class="mx-auto max-w-[1500px] space-y-6">

        {{-- =========================================================
            HERO & MONTH CONTROLS
        ========================================================== --}}
        <section class="relative overflow-hidden rounded-3xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900 transition-colors duration-200">
            <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-blue-100/60 blur-3xl dark:bg-blue-900/20"></div>
            <div class="pointer-events-none absolute right-52 top-12 h-32 w-32 rounded-full bg-indigo-100/60 blur-3xl dark:bg-indigo-900/20"></div>

            <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="mb-3 flex items-center gap-2">
                        <span class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-700 dark:bg-blue-950/70 dark:text-blue-400">
                            <span class="h-1.5 w-1.5 rounded-full bg-blue-500 animate-ping"></span>
                            <span data-i18n="calendar">Interactive Schedule</span>
                        </span>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                            {{ $calendarMonth->format('F Y') }}
                        </span>
                    </div>

                    <h1 class="text-3xl font-extrabold tracking-tight text-slate-950 sm:text-4xl dark:text-white" data-i18n="calendar">
                        Calendar
                    </h1>

                    <p class="mt-2 max-w-xl text-sm leading-6 text-slate-500 sm:text-base dark:text-slate-400" data-i18n="next_deadlines">
                        Visualize your task timeline, deadlines, and plan workload across days and weeks.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    {{-- Month Navigator --}}
                    <div class="flex items-center rounded-xl border border-slate-200 bg-white p-1 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                        <a href="{{ route('calendar', ['month' => $prevMonth->format('Y-m')]) }}"
                           class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700 transition"
                           title="Previous Month">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                        </a>
                        <span class="px-3 text-xs font-extrabold text-slate-800 dark:text-slate-200 whitespace-nowrap">
                            {{ $calendarMonth->format('M Y') }}
                        </span>
                        <a href="{{ route('calendar', ['month' => $nextMonth->format('Y-m')]) }}"
                           class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700 transition"
                           title="Next Month">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                        </a>
                    </div>

                    <a href="{{ route('calendar') }}"
                        class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-3.5 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                        Today
                    </a>

                    <a href="{{ route('tasks.index') }}"
                        class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                        <x-icon name="kanban" class="h-4 w-4" />
                        <span data-i18n="my_tasks">Kanban</span>
                    </a>

                    <button type="button" data-open-task-modal
                        class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-blue-950 px-5 text-sm font-semibold text-white shadow-lg shadow-blue-950/10 transition duration-200 hover:-translate-y-0.5 hover:bg-blue-900 dark:bg-blue-600 dark:hover:bg-blue-500">
                        <x-icon name="plus" class="h-4 w-4" />
                        <span data-i18n="new_task">Add Task</span>
                    </button>
                </div>
            </div>

            {{-- Summary stats strip --}}
            <div class="mt-6 pt-6 border-t border-slate-100 dark:border-slate-800 grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="rounded-xl bg-slate-50/70 p-3 dark:bg-slate-800/40">
                    <span class="text-xs font-semibold text-slate-400">Total Tasks</span>
                    <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">{{ $totalCount }}</p>
                </div>
                <div class="rounded-xl bg-blue-50/70 p-3 dark:bg-blue-950/30">
                    <span class="text-xs font-semibold text-blue-600 dark:text-blue-400">Scheduled Deadlines</span>
                    <p class="mt-1 text-2xl font-bold text-blue-700 dark:text-blue-300">{{ $scheduledCount }}</p>
                </div>
                <div class="rounded-xl bg-emerald-50/70 p-3 dark:bg-emerald-950/30">
                    <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">Due Today</span>
                    <p class="mt-1 text-2xl font-bold text-emerald-700 dark:text-emerald-300">{{ $todayCount }}</p>
                </div>
                <div class="rounded-xl bg-amber-50/70 p-3 dark:bg-amber-950/30">
                    <span class="text-xs font-semibold text-amber-600 dark:text-amber-400">Unscheduled</span>
                    <p class="mt-1 text-2xl font-bold text-amber-700 dark:text-amber-300">{{ $unscheduledCount }}</p>
                </div>
            </div>
        </section>

        {{-- =========================================================
            INTERACTIVE MONTH GRID
        ========================================================== --}}
        <section class="rounded-3xl border border-slate-200/80 bg-white p-5 sm:p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 transition-colors duration-200">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <x-icon name="calendar" class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                    Month Overview · {{ $calendarMonth->format('F Y') }}
                </h2>
                <span class="text-xs text-slate-400">Click a date to jump to its agenda</span>
            </div>

            {{-- Day of week headers --}}
            <div class="grid grid-cols-7 gap-1 text-center text-xs font-bold text-slate-400 mb-2">
                <div>Mon</div>
                <div>Tue</div>
                <div>Wed</div>
                <div>Thu</div>
                <div>Fri</div>
                <div class="text-indigo-600 dark:text-indigo-400">Sat</div>
                <div class="text-indigo-600 dark:text-indigo-400">Sun</div>
            </div>

            {{-- Calendar Days Grid --}}
            <div class="grid grid-cols-7 gap-1 sm:gap-2">
                {{-- Leading blanks --}}
                @for ($i = 0; $i < $startDayOfWeek; $i++)
                    <div class="min-h-16 sm:min-h-24 rounded-2xl bg-slate-50/40 p-2 dark:bg-slate-800/20 border border-transparent opacity-40"></div>
                @endfor

                {{-- Days of the current month --}}
                @for ($day = 1; $day <= $daysInMonth; $day++)
                    @php
                        $dayDate = $calendarMonth->copy()->day($day);
                        $dateStr = $dayDate->toDateString();
                        $isToday = $dayDate->isToday();
                        $dayTasks = $groupedTasks->get($dateStr, collect());
                        $hasTasks = $dayTasks->isNotEmpty();
                    @endphp

                    <a href="{{ $hasTasks ? '#date-' . $dateStr : 'javascript:void(0)' }}"
                       class="group min-h-16 sm:min-h-24 rounded-2xl border p-2 sm:p-2.5 transition-all duration-200 flex flex-col justify-between
                              {{ $isToday ? 'border-blue-500 bg-blue-50/50 shadow-xs dark:bg-blue-950/40 dark:border-blue-500' : 'border-slate-100 bg-white hover:border-slate-300 hover:shadow-sm dark:border-slate-800/80 dark:bg-slate-850 dark:hover:border-slate-700' }}
                              {{ $hasTasks ? 'cursor-pointer' : '' }}">
                        <div class="flex items-center justify-between">
                            <span class="flex h-6 w-6 items-center justify-center rounded-lg text-xs font-bold
                                         {{ $isToday ? 'bg-blue-600 text-white' : 'text-slate-700 group-hover:text-slate-900 dark:text-slate-300 dark:group-hover:text-white' }}">
                                {{ $day }}
                            </span>

                            @if ($hasTasks)
                                <span class="rounded-full bg-blue-100 px-1.5 py-0.5 text-[10px] font-bold text-blue-700 dark:bg-blue-900 dark:text-blue-300">
                                    {{ $dayTasks->count() }}
                                </span>
                            @endif
                        </div>

                        {{-- Task previews or dots --}}
                        <div class="mt-1 space-y-1">
                            @foreach ($dayTasks->take(2) as $t)
                                <div class="hidden sm:block truncate rounded px-1.5 py-0.5 text-[10px] font-semibold
                                            {{ $t->status === 'completed' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400 line-through' : 'bg-slate-100 text-slate-800 dark:bg-slate-750 dark:text-slate-200' }}">
                                    {{ $t->title }}
                                </div>
                            @endforeach

                            {{-- Mobile dots --}}
                            @if ($hasTasks)
                                <div class="flex sm:hidden items-center gap-1 justify-center">
                                    @foreach ($dayTasks->take(3) as $t)
                                        <span class="h-1.5 w-1.5 rounded-full {{ $t->priority === 'high' ? 'bg-rose-500' : ($t->priority === 'medium' ? 'bg-amber-500' : 'bg-emerald-500') }}"></span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </a>
                @endfor
            </div>
        </section>

        {{-- =========================================================
            AGENDA & UNSCHEDULED BACKLOG (2 COLUMNS)
        ========================================================== --}}
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">

            {{-- Scheduled Tasks Agenda --}}
            <section class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 transition-colors duration-200">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5 dark:border-slate-800">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400">
                            <x-icon name="calendar" class="h-5 w-5" />
                        </span>
                        <div>
                            <h2 class="font-bold text-slate-950 dark:text-white">Scheduled Agenda</h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Tasks organized chronologically by due date</p>
                        </div>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                        {{ $scheduledCount }} Tasks
                    </span>
                </div>

                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($groupedTasks as $date => $tasks)
                        @php
                            $calendarDate = \Carbon\Carbon::parse($date);
                            $isToday = $calendarDate->isToday();
                            $isPast = $calendarDate->isPast() && !$calendarDate->isToday();
                            $dateTone = $isToday
                                ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20'
                                : ($isPast ? 'bg-rose-500 text-white' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300');
                        @endphp

                        <div id="date-{{ $date }}" class="scroll-mt-24 p-6 transition hover:bg-slate-50/40 dark:hover:bg-slate-850/40">
                            {{-- Date Banner --}}
                            <div class="mb-4 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-12 w-12 shrink-0 flex-col items-center justify-center rounded-xl {{ $dateTone }}">
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
                            </div>

                            {{-- Task Cards for this date --}}
                            <div class="space-y-3">
                                @foreach ($tasks as $task)
                                    <div class="group relative flex flex-col gap-3 rounded-2xl border border-slate-200/70 bg-white p-4 transition-all duration-200 hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md sm:flex-row sm:items-center sm:justify-between dark:border-slate-800 dark:bg-slate-850 dark:hover:border-slate-700">
                                        <div class="flex items-start gap-3 min-w-0">
                                            {{-- Toggle checkbox --}}
                                            <form method="POST" action="{{ route('tasks.toggle-status', $task) }}" class="mt-0.5">
                                                @csrf @method('PATCH')
                                                <button type="submit"
                                                    class="flex h-6 w-6 items-center justify-center rounded-lg border transition {{ $task->status === 'completed' ? 'bg-emerald-500 border-emerald-500 text-white' : 'border-slate-300 dark:border-slate-700 text-transparent hover:text-slate-400 hover:border-slate-400' }}">
                                                    <x-icon name="check" class="h-3.5 w-3.5" />
                                                </button>
                                            </form>

                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <a href="{{ route('tasks.show', $task) }}"
                                                       class="font-bold text-sm text-slate-900 dark:text-white hover:text-blue-600 transition {{ $task->status === 'completed' ? 'line-through text-slate-400 dark:text-slate-500' : '' }}">
                                                        {{ $task->title }}
                                                    </a>

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
                                                </div>

                                                @if ($task->description)
                                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 line-clamp-1">
                                                        {{ $task->description }}
                                                    </p>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Actions --}}
                                        <div class="flex items-center gap-2 shrink-0 self-end sm:self-center">
                                            <a href="{{ route('tasks.edit', $task) }}"
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

            {{-- Unscheduled Tasks Backlog --}}
            <aside class="space-y-6">
                <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 transition-colors duration-200">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
                        <div>
                            <h3 class="font-bold text-slate-900 dark:text-white">Unscheduled Backlog</h3>
                            <p class="text-xs text-slate-400">Tasks without due dates</p>
                        </div>
                        <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700 dark:bg-amber-950 dark:text-amber-400">
                            {{ $unscheduledCount }}
                        </span>
                    </div>

                    <div class="mt-4 space-y-3 max-h-[500px] overflow-y-auto pr-1">
                        @forelse ($unscheduledTasks as $task)
                            <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-3.5 transition hover:bg-slate-100/60 dark:border-slate-800 dark:bg-slate-800/40 dark:hover:bg-slate-800/80">
                                <div class="flex items-center justify-between gap-2">
                                    <a href="{{ route('tasks.show', $task) }}" class="text-xs font-bold text-slate-800 dark:text-slate-200 hover:text-blue-600 truncate">
                                        {{ $task->title }}
                                    </a>
                                    <span class="h-2 w-2 rounded-full shrink-0 {{ $task->priority === 'high' ? 'bg-rose-500' : ($task->priority === 'medium' ? 'bg-amber-500' : 'bg-emerald-500') }}"></span>
                                </div>
                                <div class="mt-2.5 flex items-center justify-between text-[11px] text-slate-400">
                                    <span>{{ $task->category ?: 'General' }}</span>
                                    <a href="{{ route('tasks.edit', $task) }}" class="font-bold text-blue-600 dark:text-blue-400 hover:underline">
                                        Set Date →
                                    </a>
                                </div>
                            </div>
                        @empty
                            <p class="py-8 text-center text-xs text-slate-400">All tasks have due dates scheduled!</p>
                        @endforelse
                    </div>
                </div>
            </aside>

        </div>

    </div>
@endsection
