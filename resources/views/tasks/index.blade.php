@extends('layouts.app')

@section('title', 'My Tasks - Workspace')

@section('content')
    @php
        $activeStatus = $activeStatus ?? request('status');
        $activePriority = $activePriority ?? request('priority');
        $activeCategory = $activeCategory ?? request('category');
        $activeView = request('view', 'kanban');

        $pendingTasks = $tasks->where('status', 'pending');
        $inProgressTasks = $tasks->where('status', 'in_progress');
        $completedTasks = $tasks->where('status', 'completed');

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

    <div class="mx-auto max-w-[1600px] space-y-6">

        {{-- =========================================================
            HEADER & HERO SECTION
        ========================================================== --}}
        <section class="relative overflow-hidden rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm sm:p-8 dark:border-slate-800 dark:bg-slate-900">
            {{-- Decorative blurs --}}
            <div class="pointer-events-none absolute -right-20 -top-24 h-64 w-64 rounded-full bg-blue-100/60 blur-3xl dark:bg-blue-900/20"></div>
            <div class="pointer-events-none absolute right-48 top-10 h-32 w-32 rounded-full bg-indigo-100/50 blur-3xl dark:bg-indigo-900/20"></div>

            <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="mb-3 flex items-center gap-2">
                        <span class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-700 dark:bg-blue-950/60 dark:text-blue-400">
                            <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                            <span data-i18n="workspace">Workspace</span>
                        </span>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                            <span id="header-total-count">{{ $tasks->count() }}</span> <span data-i18n="total_tasks">Tasks</span>
                        </span>
                    </div>

                    <h1 class="text-3xl font-extrabold tracking-tight text-slate-950 sm:text-4xl dark:text-white" data-i18n="my_tasks">
                        My Tasks
                    </h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500 sm:text-base dark:text-slate-400" data-i18n="my_tasks_sub">
                        Plan, prioritize, and track work from one interactive Kanban and list board.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    {{-- View mode switcher --}}
                    <div class="flex items-center rounded-xl border border-slate-200 bg-slate-50 p-1 dark:border-slate-700 dark:bg-slate-800">
                        <button type="button" data-view-btn="kanban"
                            class="view-btn flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold transition {{ $activeView === 'kanban' ? 'bg-white text-blue-600 shadow-sm dark:bg-slate-900 dark:text-blue-400' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white' }}">
                            <x-icon name="kanban" class="h-3.5 w-3.5" />
                            <span data-i18n="view_kanban">Kanban</span>
                        </button>
                        <button type="button" data-view-btn="list"
                            class="view-btn flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold transition {{ $activeView === 'list' ? 'bg-white text-blue-600 shadow-sm dark:bg-slate-900 dark:text-blue-400' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white' }}">
                            <x-icon name="list" class="h-3.5 w-3.5" />
                            <span data-i18n="view_list">List</span>
                        </button>
                        <button type="button" data-view-btn="grid"
                            class="view-btn flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold transition {{ $activeView === 'grid' ? 'bg-white text-blue-600 shadow-sm dark:bg-slate-900 dark:text-blue-400' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white' }}">
                            <x-icon name="grid" class="h-3.5 w-3.5" />
                            <span data-i18n="view_grid">Grid</span>
                        </button>
                    </div>

                    {{-- Export Menu --}}
                    <div class="relative group">
                        <button type="button"
                            class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                            <x-icon name="download" class="h-4 w-4" />
                            <span data-i18n="export">Export</span>
                        </button>
                        <div class="absolute right-0 top-12 z-20 hidden w-44 rounded-xl border border-slate-200 bg-white p-1.5 shadow-xl group-hover:block dark:border-slate-700 dark:bg-slate-800">
                            <a href="{{ route('tasks.export', 'csv') }}" class="block rounded-lg px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-700">
                                📊 Export as CSV
                            </a>
                            <a href="{{ route('tasks.export', 'json') }}" class="block rounded-lg px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-700">
                                📄 Export as JSON
                            </a>
                        </div>
                    </div>

                    {{-- Add Task Button --}}
                    <button type="button" data-open-task-modal
                        class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-blue-950 px-5 text-sm font-semibold text-white shadow-lg shadow-blue-950/10 transition hover:-translate-y-0.5 hover:bg-blue-900 dark:bg-blue-600 dark:hover:bg-blue-500">
                        <x-icon name="plus" class="h-4 w-4" />
                        <span data-i18n="new_task">New Task</span>
                    </button>
                </div>
            </div>
        </section>

        {{-- =========================================================
            INLINE QUICK-ADD TASK BAR
        ========================================================== --}}
        <section class="quick-add-bar relative overflow-hidden rounded-2xl border border-blue-200/80 bg-white p-3 shadow-md sm:p-4 dark:border-slate-800 dark:bg-slate-900">
            <form id="quick-add-form" class="flex flex-col gap-3 sm:flex-row sm:items-center">
                @csrf
                <div class="relative flex-1">
                    <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-purple-600 dark:text-purple-400">
                        <x-icon name="sparkles" class="h-4 w-4" />
                    </span>
                    <input
                        type="text"
                        name="title"
                        id="quick-add-title"
                        placeholder="✨ Smart AI Input: Type e.g. 'Fix login auth bug by Friday priority high #Dev' / បន្ថែម Task..."
                        required
                        autocomplete="off"
                        class="h-11 w-full rounded-xl border border-transparent bg-slate-50 pl-10 pr-4 text-sm font-medium text-slate-900 outline-none placeholder:text-slate-400 focus:border-purple-500 focus:bg-white focus:ring-2 focus:ring-purple-100 dark:bg-slate-800 dark:text-white dark:focus:ring-purple-900/40"
                    >
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    {{-- Quick category --}}
                    <select
                        name="category"
                        id="quick-add-category"
                        class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 outline-none hover:border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                    >
                        <option value="">📁 Category</option>
                        <option value="Work">💼 Work</option>
                        <option value="Personal">🏠 Personal</option>
                        <option value="Urgent">⚡ Urgent</option>
                        <option value="Design">🎨 Design</option>
                        <option value="Dev">💻 Dev</option>
                        <option value="Study">📚 Study</option>
                        <option value="Finance">💵 Finance</option>
                    </select>

                    {{-- Quick Priority --}}
                    <select
                        name="priority"
                        id="quick-add-priority"
                        class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 outline-none hover:border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                    >
                        <option value="medium">🟡 Medium</option>
                        <option value="high">🔴 High</option>
                        <option value="low">🟢 Low</option>
                    </select>

                    {{-- Quick Date --}}
                    <input
                        type="date"
                        name="due_date"
                        id="quick-add-due-date"
                        class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 outline-none hover:border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                    >

                    <button
                        type="submit"
                        id="quick-add-submit"
                        class="inline-flex h-11 items-center justify-center gap-1.5 rounded-xl bg-blue-600 px-5 text-xs font-bold text-white shadow-sm transition hover:bg-blue-700"
                    >
                        <x-icon name="plus" class="h-3.5 w-3.5" />
                        <span data-i18n="add">Add</span>
                    </button>
                </div>
            </form>

            {{-- AI Natural Language Live Detection Preview --}}
            <div id="quick-add-nlp-preview" class="hidden mt-2.5 flex flex-wrap items-center gap-2 rounded-xl border border-purple-200/70 bg-purple-50/80 px-3 py-2 text-xs text-purple-900 dark:border-purple-900/50 dark:bg-purple-950/40 dark:text-purple-200">
                <span class="flex items-center gap-1 font-bold text-purple-700 dark:text-purple-300">
                    <x-icon name="sparkles" class="h-3.5 w-3.5 text-purple-600 dark:text-purple-400" />
                    <span data-i18n="ai_copilot_detected">AI Copilot detected:</span>
                </span>
                <span id="nlp-chip-title" class="font-semibold text-slate-800 dark:text-slate-200"></span>
                <span id="nlp-chip-date" class="hidden inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-bold text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300"></span>
                <span id="nlp-chip-priority" class="hidden inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-[11px] font-bold text-amber-800 dark:bg-amber-950/70 dark:text-amber-300"></span>
                <span id="nlp-chip-category" class="hidden inline-flex items-center gap-1 rounded-full bg-blue-100 px-2.5 py-0.5 text-[11px] font-bold text-blue-800 dark:bg-blue-950/70 dark:text-blue-300"></span>
            </div>
        </section>

        {{-- =========================================================
            FILTER & SEARCH TOOLBAR
        ========================================================== --}}
        <section class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                {{-- Status Pills --}}
                <div class="flex max-w-full gap-1.5 overflow-x-auto rounded-xl bg-slate-50 p-1.5 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden dark:bg-slate-800">
                    <a href="{{ route('tasks.index', request()->except('status')) }}"
                        class="inline-flex h-9 shrink-0 items-center gap-2 rounded-lg px-3.5 text-xs font-bold transition {{ !$activeStatus ? 'bg-white text-blue-700 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:text-blue-400 dark:ring-slate-700' : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' }}">
                        <x-icon name="clipboard" class="h-3.5 w-3.5" />
                        <span data-i18n="all">All</span>
                        <span class="rounded-full bg-slate-100 px-1.5 py-0.5 text-[10px] text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $tasks->count() }}</span>
                    </a>

                    <a href="{{ route('tasks.index', array_merge(request()->except('status'), ['status' => 'pending'])) }}"
                        class="inline-flex h-9 shrink-0 items-center gap-2 rounded-lg px-3.5 text-xs font-bold transition {{ $activeStatus === 'pending' ? 'bg-white text-amber-700 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:text-amber-400 dark:ring-slate-700' : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' }}">
                        <span class="h-2 w-2 rounded-full bg-amber-400"></span>
                        <span data-i18n="pending">Pending</span>
                        <span class="rounded-full bg-amber-100/60 px-1.5 py-0.5 text-[10px] text-amber-700 dark:bg-amber-950 dark:text-amber-400">{{ $pendingTasks->count() }}</span>
                    </a>

                    <a href="{{ route('tasks.index', array_merge(request()->except('status'), ['status' => 'in_progress'])) }}"
                        class="inline-flex h-9 shrink-0 items-center gap-2 rounded-lg px-3.5 text-xs font-bold transition {{ $activeStatus === 'in_progress' ? 'bg-white text-blue-700 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:text-blue-400 dark:ring-slate-700' : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' }}">
                        <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                        <span data-i18n="in_progress">In Progress</span>
                        <span class="rounded-full bg-blue-100/60 px-1.5 py-0.5 text-[10px] text-blue-700 dark:bg-blue-950 dark:text-blue-400">{{ $inProgressTasks->count() }}</span>
                    </a>

                    <a href="{{ route('tasks.index', array_merge(request()->except('status'), ['status' => 'completed'])) }}"
                        class="inline-flex h-9 shrink-0 items-center gap-2 rounded-lg px-3.5 text-xs font-bold transition {{ $activeStatus === 'completed' ? 'bg-white text-emerald-700 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:text-emerald-400 dark:ring-slate-700' : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' }}">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        <span data-i18n="completed">Completed</span>
                        <span class="rounded-full bg-emerald-100/60 px-1.5 py-0.5 text-[10px] text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400">{{ $completedTasks->count() }}</span>
                    </a>
                </div>

                {{-- Search & Dropdowns --}}
                <form method="GET" action="{{ route('tasks.index') }}" class="flex flex-wrap items-center gap-3">
                    @if ($activeStatus)
                        <input type="hidden" name="status" value="{{ $activeStatus }}">
                    @endif
                    <input type="hidden" name="view" value="{{ $activeView }}" id="current-view-input">

                    {{-- Search --}}
                    <div class="relative flex-1 sm:w-64">
                        <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <input
                            type="search"
                            name="q"
                            value="{{ request('q') }}"
                            placeholder="Search tasks..."
                            class="h-10 w-full rounded-xl border border-slate-200 bg-white pl-9 pr-4 text-xs text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                        >
                    </div>

                    {{-- Priority Filter --}}
                    <select
                        name="priority"
                        data-submit-on-change
                        class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 outline-none hover:border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                    >
                        <option value="" data-i18n="all_priority">All Priority</option>
                        <option value="high" @selected($activePriority === 'high') data-i18n="high">🔴 High</option>
                        <option value="medium" @selected($activePriority === 'medium') data-i18n="medium">🟡 Medium</option>
                        <option value="low" @selected($activePriority === 'low') data-i18n="low">🟢 Low</option>
                    </select>

                    {{-- Category Filter --}}
                    <select
                        name="category"
                        data-submit-on-change
                        class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 outline-none hover:border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                    >
                        <option value="" data-i18n="all_categories">All Categories</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat }}" @selected($activeCategory === $cat)>{{ $cat }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            {{-- Quick Workflow Presets --}}
            <div class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-800 flex flex-wrap items-center gap-2 text-xs">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Quick Filters:</span>
                <a href="{{ route('tasks.index', request('due') === 'today' ? request()->except('due') : array_merge(request()->all(), ['due' => 'today'])) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 font-semibold transition {{ request('due') === 'today' ? 'bg-blue-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300' }}">
                    <span>☀️ Today</span>
                </a>
                <a href="{{ route('tasks.index', request('due') === 'overdue' ? request()->except('due') : array_merge(request()->all(), ['due' => 'overdue'])) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 font-semibold transition {{ request('due') === 'overdue' ? 'bg-rose-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300' }}">
                    <span>⚠️ Overdue</span>
                </a>
                <a href="{{ route('tasks.index', request()->boolean('pinned') ? request()->except('pinned') : array_merge(request()->all(), ['pinned' => '1'])) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 font-semibold transition {{ request()->boolean('pinned') ? 'bg-amber-500 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300' }}">
                    <span>📌 Pinned</span>
                </a>
                <a href="{{ route('tasks.index', request('priority') === 'high' ? request()->except('priority') : array_merge(request()->all(), ['priority' => 'high'])) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 font-semibold transition {{ request('priority') === 'high' ? 'bg-rose-500 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300' }}">
                    <span>🔴 High Priority</span>
                </a>
                @if (request()->hasAny(['due', 'pinned', 'priority', 'status', 'category', 'q']))
                    <a href="{{ route('tasks.index') }}" class="ml-auto font-bold text-blue-600 dark:text-blue-400 hover:underline">
                        Clear all filters
                    </a>
                @endif
            </div>
        </section>


        {{-- =========================================================
            VIEW 1: KANBAN BOARD (DRAG AND DROP)
        ========================================================== --}}
        <section data-view-panel="kanban" class="{{ $activeView === 'kanban' ? '' : 'hidden' }}">
            <div class="kanban-board">

                {{-- COLUMN 1: PENDING --}}
                <div class="kanban-col flex flex-col" data-status-col="pending">
                    <div class="mb-4 flex items-center justify-between border-b border-slate-200/60 pb-3 dark:border-slate-800">
                        <div class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white" data-i18n="pending">Pending</h3>
                            <span class="col-count rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-800 dark:bg-amber-950 dark:text-amber-300">
                                {{ $pendingTasks->count() }}
                            </span>
                        </div>
                        <button type="button" data-open-task-modal class="rounded-lg p-1 text-slate-400 hover:bg-slate-200 hover:text-slate-700 dark:hover:bg-slate-800" title="Add task">
                            <x-icon name="plus" class="h-4 w-4" />
                        </button>
                    </div>

                    <div class="kanban-cards flex-1 space-y-3" data-col-cards="pending">
                        @forelse ($pendingTasks as $task)
                            @include('tasks._card', ['task' => $task])
                        @empty
                            <div class="empty-drop-placeholder flex h-36 flex-col items-center justify-center rounded-xl border border-dashed border-slate-200 p-4 text-center text-xs text-slate-400 dark:border-slate-800">
                                <x-icon name="inbox" class="mb-1 h-6 w-6 opacity-60" />
                                <span data-i18n="no_pending_tasks">No pending tasks. Drag cards here!</span>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- COLUMN 2: IN PROGRESS --}}
                <div class="kanban-col flex flex-col" data-status-col="in_progress">
                    <div class="mb-4 flex items-center justify-between border-b border-slate-200/60 pb-3 dark:border-slate-800">
                        <div class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-full bg-blue-500"></span>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white" data-i18n="in_progress">In Progress</h3>
                            <span class="col-count rounded-full bg-blue-100 px-2 py-0.5 text-xs font-bold text-blue-800 dark:bg-blue-950 dark:text-blue-300">
                                {{ $inProgressTasks->count() }}
                            </span>
                        </div>
                        <button type="button" data-open-task-modal class="rounded-lg p-1 text-slate-400 hover:bg-slate-200 hover:text-slate-700 dark:hover:bg-slate-800" title="Add task">
                            <x-icon name="plus" class="h-4 w-4" />
                        </button>
                    </div>

                    <div class="kanban-cards flex-1 space-y-3" data-col-cards="in_progress">
                        @forelse ($inProgressTasks as $task)
                            @include('tasks._card', ['task' => $task])
                        @empty
                            <div class="empty-drop-placeholder flex h-36 flex-col items-center justify-center rounded-xl border border-dashed border-slate-200 p-4 text-center text-xs text-slate-400 dark:border-slate-800">
                                <x-icon name="layers" class="mb-1 h-6 w-6 opacity-60" />
                                <span data-i18n="no_in_progress_tasks">No active tasks in progress.</span>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- COLUMN 3: COMPLETED --}}
                <div class="kanban-col flex flex-col" data-status-col="completed">
                    <div class="mb-4 flex items-center justify-between border-b border-slate-200/60 pb-3 dark:border-slate-800">
                        <div class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white" data-i18n="completed">Completed</h3>
                            <span class="col-count rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                {{ $completedTasks->count() }}
                            </span>
                        </div>
                        <span class="text-xs text-emerald-600 font-bold">🎉</span>
                    </div>

                    <div class="kanban-cards flex-1 space-y-3" data-col-cards="completed">
                        @forelse ($completedTasks as $task)
                            @include('tasks._card', ['task' => $task])
                        @empty
                            <div class="empty-drop-placeholder flex h-36 flex-col items-center justify-center rounded-xl border border-dashed border-slate-200 p-4 text-center text-xs text-slate-400 dark:border-slate-800">
                                <x-icon name="check-circle" class="mb-1 h-6 w-6 opacity-60" />
                                <span data-i18n="no_completed_tasks">Drop completed cards here!</span>
                            </div>
                        @endforelse
                    </div>
                </div>

            </div>
        </section>


        {{-- =========================================================
            VIEW 2: PRODUCTIVE LIST VIEW
        ========================================================== --}}
        <section data-view-panel="list" class="{{ $activeView === 'list' ? '' : 'hidden' }} overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            @if ($tasks->isEmpty())
                <div class="flex min-h-[300px] flex-col items-center justify-center p-8 text-center">
                    <x-icon name="inbox" class="h-10 w-10 text-slate-300 dark:text-slate-600" />
                    <p class="mt-3 text-sm font-bold text-slate-800 dark:text-slate-200" data-i18n="no_tasks_found">No tasks found</p>
                </div>
            @else
                <div class="hidden grid-cols-[auto_1fr_120px_130px_140px_100px] items-center gap-4 border-b border-slate-100 bg-slate-50/70 px-6 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-400 md:grid dark:border-slate-800 dark:bg-slate-800/50">
                    <span></span>
                    <span data-i18n="task">Task</span>
                    <span data-i18n="category">Category</span>
                    <span data-i18n="priority">Priority</span>
                    <span data-i18n="due_date">Due Date</span>
                    <span class="text-right" data-i18n="actions">Actions</span>
                </div>

                <div class="divide-y divide-slate-100 dark:divide-slate-800" id="list-view-container">
                    @foreach ($tasks as $task)
                        <div class="group flex flex-col gap-3 px-5 py-4 transition hover:bg-slate-50/80 sm:px-6 md:grid md:grid-cols-[auto_1fr_120px_130px_140px_100px] md:items-center dark:hover:bg-slate-800/40"
                             id="list-row-{{ $task->id }}">
                            {{-- Checkbox --}}
                            <div class="flex items-center gap-2">
                                <button type="button" data-toggle-complete-id="{{ $task->id }}"
                                    class="flex h-5 w-5 items-center justify-center rounded-md border {{ $task->status === 'completed' ? 'border-emerald-500 bg-emerald-500 text-white' : 'border-slate-300 bg-white hover:border-slate-400 dark:border-slate-700 dark:bg-slate-800' }}">
                                    @if ($task->status === 'completed')
                                        <x-icon name="check" class="h-3 w-3" />
                                    @endif
                                </button>
                                <button type="button" data-toggle-pin-id="{{ $task->id }}"
                                    class="text-xs transition {{ $task->is_pinned ? 'text-amber-500' : 'text-slate-300 hover:text-amber-500 dark:text-slate-600' }}" title="Pin">
                                    <x-icon name="pin" class="h-3.5 w-3.5" />
                                </button>
                            </div>

                            {{-- Title & Checklist progress --}}
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="truncate text-sm font-bold text-slate-900 dark:text-white {{ $task->status === 'completed' ? 'task-completed-text' : '' }}" id="task-title-{{ $task->id }}">
                                        <a href="{{ route('tasks.show', $task) }}" class="hover:text-blue-600 dark:hover:text-blue-400">{{ $task->title }}</a>
                                    </p>
                                </div>
                                @if ($task->subtasks_count > 0)
                                    <div class="mt-1 flex items-center gap-2 text-[11px] text-slate-400">
                                        <span class="rounded bg-slate-100 px-1.5 py-0.5 font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                            ✓ {{ $task->completed_subtasks_count }}/{{ $task->subtasks_count }}
                                        </span>
                                        <div class="h-1.5 w-16 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                            <div class="h-full bg-emerald-500" style="width: {{ $task->subtasks_progress }}%"></div>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            {{-- Category --}}
                            <div>
                                @if ($task->category)
                                    <span class="inline-block rounded-full border px-2.5 py-0.5 text-[11px] font-semibold {{ $categoryColors[$task->category] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                                        {{ $task->category }}
                                    </span>
                                @else
                                    <span class="text-xs text-slate-300 dark:text-slate-600">—</span>
                                @endif
                            </div>

                            {{-- Priority --}}
                            <div>
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $priorityColors[$task->priority] ?? 'bg-slate-100 text-slate-600' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $priorityDots[$task->priority] ?? 'bg-slate-400' }}"></span>
                                    {{ ucfirst($task->priority) }}
                                </span>
                            </div>

                            {{-- Due Date --}}
                            <div>
                                @if ($task->due_date)
                                    <span class="inline-flex items-center gap-1 text-xs font-medium {{ $task->is_overdue ? 'font-bold text-rose-600 dark:text-rose-400' : 'text-slate-500 dark:text-slate-400' }}">
                                        <x-icon name="calendar" class="h-3.5 w-3.5" />
                                        {{ $task->due_date->format('M d, Y') }}
                                        @if ($task->is_overdue)
                                            <span class="rounded bg-rose-100 px-1 text-[10px] text-rose-700 dark:bg-rose-950 dark:text-rose-300">!</span>
                                        @endif
                                    </span>
                                @else
                                    <span class="text-xs text-slate-300 dark:text-slate-600">—</span>
                                @endif
                            </div>

                            {{-- Actions --}}
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('tasks.edit', $task) }}" data-edit-task-id="{{ $task->id }}" class="rounded-lg p-1.5 text-slate-400 transition hover:bg-blue-50 hover:text-blue-600 dark:hover:bg-slate-800" title="Edit">
                                    <x-icon name="edit" class="h-4 w-4" />
                                </a>
                                <form method="POST" action="{{ route('tasks.destroy', $task) }}" data-confirm="Delete this task?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg p-1.5 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-slate-800" title="Delete">
                                        <x-icon name="trash" class="h-4 w-4" />
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>


        {{-- =========================================================
            VIEW 3: GRID VIEW
        ========================================================== --}}
        <section data-view-panel="grid" class="{{ $activeView === 'grid' ? '' : 'hidden' }}">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($tasks as $task)
                    @include('tasks._card', ['task' => $task])
                @empty
                    <div class="col-span-full flex min-h-[300px] flex-col items-center justify-center rounded-2xl border border-slate-200 bg-white p-8 text-center dark:border-slate-800 dark:bg-slate-900">
                        <x-icon name="inbox" class="h-10 w-10 text-slate-300 dark:text-slate-600" />
                        <p class="mt-3 text-sm font-bold text-slate-800 dark:text-slate-200" data-i18n="no_tasks_found">No tasks found</p>
                    </div>
                @endforelse
            </div>
        </section>

    </div>
@endsection
