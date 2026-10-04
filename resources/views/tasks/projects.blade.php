@extends('layouts.app')

@section('title', 'Projects & Categories - Task Manager')

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
                        <span class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-700 dark:bg-blue-950/70 dark:text-blue-400">
                            <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                            <span data-i18n="workspace">Workspaces</span>
                        </span>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                            {{ $totalProjects }} Projects
                        </span>
                    </div>

                    <h1 class="text-3xl font-extrabold tracking-tight text-slate-950 sm:text-4xl dark:text-white" data-i18n="projects">
                        Projects & Categories
                    </h1>

                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500 sm:text-base dark:text-slate-400" data-i18n="projects_sub">
                        Organize, track, and manage your tasks categorized by project streams with live progress meters.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('tasks.create') }}" data-open-task-modal
                        class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-blue-950 px-5 text-sm font-semibold text-white shadow-lg shadow-blue-950/10 transition duration-200 hover:-translate-y-0.5 hover:bg-blue-900 dark:bg-blue-600 dark:hover:bg-blue-500">
                        <x-icon name="plus" class="h-4 w-4" />
                        <span data-i18n="new_task">New Task</span>
                    </a>

                    <a href="{{ route('analytics') }}"
                        class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                        <x-icon name="analytics" class="h-4 w-4 text-slate-500" />
                        <span data-i18n="analytics">View Analytics</span>
                    </a>
                </div>
            </div>

            {{-- Summary ribbon --}}
            <div class="mt-6 pt-6 border-t border-slate-100 dark:border-slate-800 grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div>
                    <span class="text-xs font-semibold text-slate-400">Total Projects</span>
                    <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">{{ $totalProjects }}</p>
                </div>
                <div>
                    <span class="text-xs font-semibold text-slate-400">Total Tasks</span>
                    <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">{{ $totalTasks }}</p>
                </div>
                <div>
                    <span class="text-xs font-semibold text-slate-400">Finished Tasks</span>
                    <p class="mt-1 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $overallCompleted }}</p>
                </div>
                <div>
                    <span class="text-xs font-semibold text-slate-400">Overall Progress</span>
                    <p class="mt-1 text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $overallProgress }}%</p>
                </div>
            </div>
        </section>

        {{-- =========================================================
            PROJECTS GRID
        ========================================================== --}}
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            @forelse ($projects as $project)
                @php
                    $colors = match($project['name']) {
                        'Work' => [
                            'border' => 'border-indigo-200/80 dark:border-indigo-900/60',
                            'header_bg' => 'from-indigo-50/70 to-indigo-100/40 dark:from-indigo-950/40 dark:to-indigo-900/20',
                            'badge' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300 ring-indigo-500/20',
                            'bar' => 'bg-indigo-600',
                            'accent' => 'text-indigo-600 dark:text-indigo-400',
                        ],
                        'Dev' => [
                            'border' => 'border-sky-200/80 dark:border-sky-900/60',
                            'header_bg' => 'from-sky-50/70 to-sky-100/40 dark:from-sky-950/40 dark:to-sky-900/20',
                            'badge' => 'bg-sky-50 text-sky-700 dark:bg-sky-950 dark:text-sky-300 ring-sky-500/20',
                            'bar' => 'bg-sky-500',
                            'accent' => 'text-sky-600 dark:text-sky-400',
                        ],
                        'Design' => [
                            'border' => 'border-pink-200/80 dark:border-pink-900/60',
                            'header_bg' => 'from-pink-50/70 to-pink-100/40 dark:from-pink-950/40 dark:to-pink-900/20',
                            'badge' => 'bg-pink-50 text-pink-700 dark:bg-pink-950 dark:text-pink-300 ring-pink-500/20',
                            'bar' => 'bg-pink-500',
                            'accent' => 'text-pink-600 dark:text-pink-400',
                        ],
                        'Personal' => [
                            'border' => 'border-purple-200/80 dark:border-purple-900/60',
                            'header_bg' => 'from-purple-50/70 to-purple-100/40 dark:from-purple-950/40 dark:to-purple-900/20',
                            'badge' => 'bg-purple-50 text-purple-700 dark:bg-purple-950 dark:text-purple-300 ring-purple-500/20',
                            'bar' => 'bg-purple-500',
                            'accent' => 'text-purple-600 dark:text-purple-400',
                        ],
                        'Urgent' => [
                            'border' => 'border-rose-200/80 dark:border-rose-900/60',
                            'header_bg' => 'from-rose-50/70 to-rose-100/40 dark:from-rose-950/40 dark:to-rose-900/20',
                            'badge' => 'bg-rose-50 text-rose-700 dark:bg-rose-950 dark:text-rose-400 ring-rose-500/20',
                            'bar' => 'bg-rose-500',
                            'accent' => 'text-rose-600 dark:text-rose-400',
                        ],
                        'Finance' => [
                            'border' => 'border-emerald-200/80 dark:border-emerald-900/60',
                            'header_bg' => 'from-emerald-50/70 to-emerald-100/40 dark:from-emerald-950/40 dark:to-emerald-900/20',
                            'badge' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 ring-emerald-500/20',
                            'bar' => 'bg-emerald-500',
                            'accent' => 'text-emerald-600 dark:text-emerald-400',
                        ],
                        default => [
                            'border' => 'border-slate-200/80 dark:border-slate-800',
                            'header_bg' => 'from-slate-50/70 to-slate-100/40 dark:from-slate-850 dark:to-slate-800/40',
                            'badge' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 ring-slate-500/20',
                            'bar' => 'bg-blue-600',
                            'accent' => 'text-blue-600 dark:text-blue-400',
                        ],
                    };
                @endphp

                <div class="group relative flex flex-col justify-between overflow-hidden rounded-3xl border {{ $colors['border'] }} bg-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl dark:bg-slate-900">

                    {{-- Card Header Banner --}}
                    <div class="bg-gradient-to-r {{ $colors['header_bg'] }} p-6 border-b border-slate-100 dark:border-slate-800/60">
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-2 rounded-xl px-3 py-1.5 text-xs font-bold ring-1 {{ $colors['badge'] }}">
                                <x-icon name="project" class="h-4 w-4" />
                                {{ $project['name'] }}
                            </span>

                            <span class="text-sm font-extrabold text-slate-900 dark:text-white">
                                {{ $project['progress'] }}%
                            </span>
                        </div>

                        {{-- Progress bar --}}
                        <div class="mt-4 h-2 w-full overflow-hidden rounded-full bg-slate-200/70 dark:bg-slate-800">
                            <div class="h-full rounded-full {{ $colors['bar'] }} transition-all duration-500" style="width: {{ $project['progress'] }}%"></div>
                        </div>

                        {{-- Micro stat counters --}}
                        <div class="mt-3 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                            <span>{{ $project['completed'] }}/{{ $project['total'] }} completed</span>
                            @if ($project['overdue'] > 0)
                                <span class="font-bold text-rose-500">{{ $project['overdue'] }} overdue</span>
                            @elseif ($project['high_priority'] > 0)
                                <span class="font-bold text-amber-500">{{ $project['high_priority'] }} urgent</span>
                            @else
                                <span class="text-emerald-600 dark:text-emerald-400">On Track</span>
                            @endif
                        </div>
                    </div>

                    {{-- Card Body: Top tasks in project --}}
                    <div class="flex-1 p-6 space-y-3">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Active Tasks</p>

                        @if ($project['tasks']->isNotEmpty())
                            <div class="space-y-2">
                                @foreach ($project['tasks'] as $task)
                                    <div class="flex items-center justify-between rounded-xl border border-slate-100 p-2.5 transition hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800/60">
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <form method="POST" action="{{ route('tasks.toggle-status', $task) }}">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="flex h-6 w-6 items-center justify-center rounded-md border {{ $task->status === 'completed' ? 'bg-emerald-500 border-emerald-500 text-white' : 'border-slate-300 dark:border-slate-700 text-transparent hover:text-slate-400' }}">
                                                    <x-icon name="check" class="h-3 w-3" />
                                                </button>
                                            </form>
                                            <a href="{{ route('tasks.show', $task) }}" class="truncate text-xs font-semibold {{ $task->status === 'completed' ? 'line-through text-slate-400' : 'text-slate-800 dark:text-slate-200 hover:text-blue-600' }}">
                                                {{ $task->title }}
                                            </a>
                                        </div>

                                        @if ($task->priority === 'high')
                                            <span class="h-1.5 w-1.5 rounded-full bg-rose-500 shrink-0" title="High Priority"></span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="py-6 text-center text-xs text-slate-400">
                                No active tasks in this project yet.
                            </div>
                        @endif
                    </div>

                    {{-- Card Footer --}}
                    <div class="border-t border-slate-100 p-4 bg-slate-50/60 dark:border-slate-800 dark:bg-slate-900/60 flex items-center justify-between">
                        <a href="{{ route('tasks.index', ['category' => $project['name']]) }}"
                           class="inline-flex items-center gap-1.5 text-xs font-bold {{ $colors['accent'] }} hover:underline">
                            <span>Open in Kanban</span>
                            <span>→</span>
                        </a>

                        <a href="{{ route('tasks.create', ['category' => $project['name']]) }}" data-open-task-modal
                           class="inline-flex h-8 items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                            <x-icon name="plus" class="h-3 w-3" />
                            <span>Add</span>
                        </a>
                    </div>

                </div>
            @empty
                <div class="col-span-full py-16 text-center">
                    <p class="text-sm font-semibold text-slate-500">No projects found.</p>
                </div>
            @endforelse
        </div>

    </div>
@endsection
