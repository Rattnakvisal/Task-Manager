@extends('layouts.app')

@section('content')
    @php
        $priorityColors = [
            'low' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/10',
            'medium' => 'bg-amber-50 text-amber-700 ring-amber-600/10',
            'high' => 'bg-rose-50 text-rose-700 ring-rose-600/10',
        ];

        $priorityDots = [
            'low' => 'bg-emerald-500',
            'medium' => 'bg-amber-500',
            'high' => 'bg-rose-500',
        ];

        $statusColors = [
            'pending' => 'bg-slate-100 text-slate-600 ring-slate-500/10',
            'in_progress' => 'bg-blue-50 text-blue-700 ring-blue-600/10',
            'completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/10',
        ];
    @endphp

    <div class="dashboard-motion min-h-screen" data-dashboard>
        <div class="mx-auto max-w-[1500px]">

            {{-- =========================================================
                HEADER / HERO
            ========================================================== --}}
            <section class="dashboard-hero relative mb-7 overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm">
                {{-- Decorative background --}}
                <div class="pointer-events-none absolute -right-28 -top-28 h-80 w-80 rounded-full bg-blue-100/70 blur-3xl">
                </div>

                <div class="pointer-events-none absolute right-44 top-16 h-36 w-36 rounded-full bg-indigo-100/50 blur-3xl">
                </div>

                <div
                    class="relative flex flex-col gap-6 px-6 py-7 sm:px-8 lg:flex-row lg:items-center lg:justify-between lg:px-10 lg:py-9">

                    <div class="max-w-2xl">
                        <div class="mb-4 flex items-center gap-2">
                            <span
                                class="inline-flex h-8 items-center rounded-full bg-blue-50 px-3 text-xs font-bold uppercase tracking-wider text-blue-700">
                                Workspace
                            </span>

                            <span class="text-xs font-medium text-slate-400">
                                Task overview
                            </span>
                        </div>

                        <h1 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">
                            Welcome to your dashboard
                        </h1>

                        <p class="mt-3 max-w-xl text-sm leading-6 text-slate-500 sm:text-base">
                            Stay focused on what matters most. Review deadlines,
                            priorities, and recent task activity from one place.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <a href="{{ route('calendar') }}"
                            class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50">
                            <x-icon name="calendar" class="h-4 w-4" />
                            Calendar
                        </a>

                        <a href="{{ route('tasks.index') }}#new-task"
                            class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-blue-950 px-5 text-sm font-semibold text-white shadow-lg shadow-blue-950/10 transition hover:-translate-y-0.5 hover:bg-blue-900">
                            <x-icon name="plus" class="h-4 w-4" />
                            Add New Task
                        </a>
                    </div>
                </div>
            </section>


            {{-- =========================================================
                STAT CARDS
            ========================================================== --}}
            <section class="dashboard-stats mb-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($stats as $label => $stat)
                    <div
                        class="dashboard-stat group relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-slate-200/60">

                        <div
                            class="absolute inset-x-0 top-0 h-0.5 bg-gradient-to-r from-transparent via-blue-500/50 to-transparent opacity-0 transition group-hover:opacity-100">
                        </div>

                        <div class="flex items-start justify-between gap-4">

                            <div>
                                <p class="text-sm font-semibold text-slate-500">
                                    {{ $label }}
                                </p>

                                <p class="mt-3 text-3xl font-bold tracking-tight text-slate-950" data-dashboard-count="{{ $stat['value'] }}">
                                    {{ $stat['value'] }}
                                </p>

                                <p class="mt-2 text-xs font-medium text-slate-400">
                                    {{ $stat['label'] }}
                                </p>
                            </div>

                            <span
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $stat['tone'] }} transition duration-300 group-hover:scale-105">
                                <x-icon :name="$stat['icon']" class="h-5 w-5" />
                            </span>
                        </div>
                    </div>
                @endforeach
            </section>


            {{-- =========================================================
                MAIN GRID
            ========================================================== --}}
            <section class="dashboard-grid grid gap-6 xl:grid-cols-[minmax(0,1.45fr)_minmax(340px,.75fr)]">

                {{-- =====================================================
                    UPCOMING TASKS
                ====================================================== --}}
                <div class="dashboard-panel overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">

                    {{-- Header --}}
                    <div
                        class="flex flex-col gap-4 border-b border-slate-100 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">

                        <div>
                            <div class="flex items-center gap-3">
                                <span
                                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                                    <x-icon name="calendar" class="h-5 w-5" />
                                </span>

                                <div>
                                    <h2 class="text-base font-bold text-slate-950">
                                        Upcoming Schedule
                                    </h2>

                                    <p class="mt-0.5 text-sm text-slate-500">
                                        Your next deadlines and scheduled work.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <a href="{{ route('calendar') }}"
                            class="inline-flex items-center justify-center rounded-lg bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-600 transition hover:bg-blue-50 hover:text-blue-700">
                            View Calendar
                        </a>
                    </div>


                    @if ($upcomingTasks->isEmpty())
                        {{-- Empty State --}}
                        <div class="dashboard-empty flex min-h-[420px] flex-col items-center justify-center px-6 py-14 text-center">

                            <div
                                class="mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                                <x-icon name="calendar" class="h-7 w-7" />
                            </div>

                            <h3 class="text-lg font-bold text-slate-950">
                                Nothing scheduled yet
                            </h3>

                            <p class="mt-2 max-w-sm text-sm leading-6 text-slate-500">
                                Tasks with a due date will automatically appear here
                                so you can quickly track upcoming work.
                            </p>

                            <a href="{{ route('tasks.index') }}#new-task"
                                class="mt-6 inline-flex h-10 items-center gap-2 rounded-xl bg-blue-950 px-4 text-sm font-semibold text-white transition hover:bg-blue-900">
                                <x-icon name="plus" class="h-4 w-4" />
                                Create Task
                            </a>
                        </div>
                    @else
                        {{-- Tasks --}}
                        <div class="divide-y divide-slate-100">

                            @foreach ($upcomingTasks as $task)
                                <div class="dashboard-list-item group relative px-5 py-5 transition hover:bg-slate-50/70 sm:px-6">

                                    <div class="flex items-start gap-4">

                                        {{-- Date Card --}}
                                        <div
                                            class="flex w-14 shrink-0 flex-col items-center overflow-hidden rounded-xl border border-slate-200 bg-white text-center shadow-sm">

                                            <div
                                                class="w-full bg-blue-950 py-1 text-[10px] font-bold uppercase tracking-wide text-white">
                                                {{ $task->due_date->format('M') }}
                                            </div>

                                            <div class="py-2">
                                                <div class="text-lg font-bold leading-none text-slate-950">
                                                    {{ $task->due_date->format('d') }}
                                                </div>

                                                <div class="mt-1 text-[10px] font-semibold text-slate-400">
                                                    {{ $task->due_date->format('D') }}
                                                </div>
                                            </div>
                                        </div>


                                        {{-- Task Information --}}
                                        <div class="min-w-0 flex-1">

                                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">

                                                <div class="min-w-0">
                                                    <div class="flex flex-wrap items-center gap-2">

                                                        <h3 class="truncate text-sm font-bold text-slate-950 sm:text-base">
                                                            {{ $task->title }}
                                                        </h3>

                                                        <span
                                                            class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1 text-[11px] font-bold ring-1 ring-inset {{ $priorityColors[$task->priority] }}">
                                                            <span
                                                                class="h-1.5 w-1.5 rounded-full {{ $priorityDots[$task->priority] }}">
                                                            </span>

                                                            {{ ucfirst($task->priority) }}
                                                        </span>

                                                        <span
                                                            class="rounded-lg px-2 py-1 text-[11px] font-bold ring-1 ring-inset {{ $statusColors[$task->status] }}">
                                                            {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                                                        </span>
                                                    </div>


                                                    @if ($task->description)
                                                        <p
                                                            class="mt-2 line-clamp-2 max-w-2xl text-sm leading-6 text-slate-500">
                                                            {{ $task->description }}
                                                        </p>
                                                    @endif


                                                    <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2">

                                                        <div
                                                            class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-400">
                                                            <x-icon name="calendar" class="h-3.5 w-3.5" />
                                                            Due
                                                            {{ $task->due_date->format('M d, Y') }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>


                        {{-- Footer --}}
                        <div
                            class="flex items-center justify-between border-t border-slate-100 bg-slate-50/60 px-5 py-4 sm:px-6">

                            <p class="text-xs font-medium text-slate-400">
                                {{ $upcomingTasks->count() }}
                                {{ Str::plural('task', $upcomingTasks->count()) }}
                                scheduled
                            </p>

                            <a href="{{ route('tasks.index') }}"
                                class="text-sm font-semibold text-blue-600 transition hover:text-blue-800">
                                View all tasks
                            </a>
                        </div>
                    @endif
                </div>


                {{-- =====================================================
                    RIGHT COLUMN
                ====================================================== --}}
                <aside class="space-y-6">


                    {{-- =================================================
                        HIGH PRIORITY
                    ================================================== --}}
                    <div class="dashboard-panel overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">

                        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-5">

                            <div class="flex items-center gap-3">

                                <span
                                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-50 text-rose-600">
                                    <span class="h-3 w-3 rounded-full bg-rose-500"></span>
                                </span>

                                <div>
                                    <h2 class="font-bold text-slate-950">
                                        High Priority
                                    </h2>

                                    <p class="mt-0.5 text-xs text-slate-500">
                                        Tasks that need attention
                                    </p>
                                </div>
                            </div>

                            <a href="{{ route('priority') }}"
                                class="rounded-lg px-3 py-2 text-xs font-bold text-blue-600 transition hover:bg-blue-50">
                                View all
                            </a>
                        </div>


                        <div class="divide-y divide-slate-100">

                            @forelse ($priorityTasks as $task)
                                <div class="dashboard-list-item group flex items-center gap-3 px-5 py-4 transition hover:bg-slate-50/70">

                                    {{-- Priority Indicator --}}
                                    <span class="h-9 w-1 shrink-0 rounded-full bg-rose-400">
                                    </span>


                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-semibold text-slate-900">
                                            {{ $task->title }}
                                        </p>

                                        <div class="mt-1.5 flex items-center gap-1 text-xs text-slate-400">

                                            <x-icon name="calendar" class="h-3.5 w-3.5" />

                                            @if ($task->due_date)
                                                Due
                                                {{ $task->due_date->format('M d, Y') }}
                                                @if ($task->end_date)
                                                    - End {{ $task->end_date->format('M d, Y') }}
                                                @endif
                                            @else
                                                No due date
                                            @endif
                                        </div>
                                    </div>


                                    <span
                                        class="shrink-0 rounded-lg px-2 py-1 text-[10px] font-bold ring-1 ring-inset {{ $statusColors[$task->status] }}">
                                        {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                                    </span>
                                </div>

                            @empty

                                <div class="dashboard-empty px-5 py-10 text-center">

                                    <div
                                        class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                                        <x-icon name="clipboard" class="h-5 w-5" />
                                    </div>

                                    <p class="text-sm font-semibold text-slate-800">
                                        No urgent tasks
                                    </p>

                                    <p class="mt-1 text-xs text-slate-400">
                                        You're clear for now.
                                    </p>
                                </div>
                            @endforelse
                        </div>
                    </div>


                    {{-- =================================================
                        RECENT ACTIVITY
                    ================================================== --}}
                    <div class="dashboard-panel overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">

                        <div class="border-b border-slate-100 px-5 py-5">

                            <div class="flex items-center gap-3">

                                <span
                                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                                    <x-icon name="clipboard" class="h-5 w-5" />
                                </span>

                                <div>
                                    <h2 class="font-bold text-slate-950">
                                        Recent Activity
                                    </h2>

                                    <p class="mt-0.5 text-xs text-slate-500">
                                        Latest tasks you've created
                                    </p>
                                </div>

                            </div>
                        </div>


                        <div class="divide-y divide-slate-100">

                            @forelse ($recentTasks as $task)
                                <div class="dashboard-list-item group flex items-center gap-3 px-5 py-4 transition hover:bg-slate-50/70">

                                    <span
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-500 transition group-hover:bg-blue-50 group-hover:text-blue-600">
                                        <x-icon name="clipboard" class="h-4 w-4" />
                                    </span>


                                    <div class="min-w-0 flex-1">

                                        <p class="truncate text-sm font-semibold text-slate-900">
                                            {{ $task->title }}
                                        </p>

                                        <p class="mt-1 text-xs text-slate-400">
                                            Added
                                            {{ $task->created_at->diffForHumans() }}
                                        </p>

                                    </div>


                                    <span class="h-2 w-2 shrink-0 rounded-full bg-blue-500 opacity-60">
                                    </span>
                                </div>

                            @empty

                                <div class="dashboard-empty px-5 py-10 text-center">

                                    <div
                                        class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-500">
                                        <x-icon name="clipboard" class="h-5 w-5" />
                                    </div>

                                    <p class="text-sm font-semibold text-slate-800">
                                        No recent activity
                                    </p>

                                    <p class="mt-1 text-xs text-slate-400">
                                        New tasks will appear here.
                                    </p>

                                </div>
                            @endforelse
                        </div>


                        @if ($recentTasks->isNotEmpty())
                            <div class="border-t border-slate-100 bg-slate-50/60 px-5 py-4 text-center">

                                <a href="{{ route('tasks.index') }}"
                                    class="text-xs font-bold text-blue-600 transition hover:text-blue-800">
                                    See all activity
                                </a>

                            </div>
                        @endif

                    </div>

                </aside>

            </section>
        </div>
    </div>
@endsection
