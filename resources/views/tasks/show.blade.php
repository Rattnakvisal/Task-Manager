@extends('layouts.app')

@section('title', $task->title . ' - WorkMind')

@php
    $priorityConfig = [
        'high' => [
            'label' => 'High Priority',
            'short' => 'High',
            'badge' => 'bg-rose-50 text-rose-700 border-rose-200/80 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-900/60',
            'dot' => 'bg-rose-500 shadow-rose-500/50',
            'accent' => 'text-rose-600 dark:text-rose-400',
        ],
        'medium' => [
            'label' => 'Medium Priority',
            'short' => 'Medium',
            'badge' => 'bg-amber-50 text-amber-700 border-amber-200/80 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-900/60',
            'dot' => 'bg-amber-500 shadow-amber-500/50',
            'accent' => 'text-amber-600 dark:text-amber-400',
        ],
        'low' => [
            'label' => 'Low Priority',
            'short' => 'Low',
            'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200/80 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-900/60',
            'dot' => 'bg-emerald-500 shadow-emerald-500/50',
            'accent' => 'text-emerald-600 dark:text-emerald-400',
        ],
    ];

    $priority = $priorityConfig[$task->priority] ?? $priorityConfig['medium'];

    $statusConfig = [
        'pending' => [
            'label' => 'Pending',
            'badge' => 'bg-amber-50 text-amber-700 border-amber-200/80 dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-900/60',
            'dot' => 'bg-amber-500',
            'glow' => 'from-amber-500/10 via-transparent to-transparent',
        ],
        'in_progress' => [
            'label' => 'In Progress',
            'badge' => 'bg-blue-50 text-blue-700 border-blue-200/80 dark:bg-blue-950/50 dark:text-blue-300 dark:border-blue-900/60',
            'dot' => 'bg-blue-500',
            'glow' => 'from-blue-500/10 via-transparent to-transparent',
        ],
        'completed' => [
            'label' => 'Completed',
            'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200/80 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-900/60',
            'dot' => 'bg-emerald-500',
            'glow' => 'from-emerald-500/10 via-transparent to-transparent',
        ],
    ];

    $status = $statusConfig[$task->status] ?? $statusConfig['pending'];

    $categoryColors = [
        'Work' => 'bg-indigo-50 text-indigo-700 border-indigo-200/80 dark:bg-indigo-950/50 dark:text-indigo-300 dark:border-indigo-900/60',
        'Personal' => 'bg-purple-50 text-purple-700 border-purple-200/80 dark:bg-purple-950/50 dark:text-purple-300 dark:border-purple-900/60',
        'Urgent' => 'bg-rose-50 text-rose-700 border-rose-200/80 dark:bg-rose-950/50 dark:text-rose-300 dark:border-rose-900/60',
        'Design' => 'bg-pink-50 text-pink-700 border-pink-200/80 dark:bg-pink-950/50 dark:text-pink-300 dark:border-pink-900/60',
        'Dev' => 'bg-sky-50 text-sky-700 border-sky-200/80 dark:bg-sky-950/50 dark:text-sky-300 dark:border-sky-900/60',
        'Study' => 'bg-teal-50 text-teal-700 border-teal-200/80 dark:bg-teal-950/50 dark:text-teal-300 dark:border-teal-900/60',
        'Finance' => 'bg-emerald-50 text-emerald-700 border-emerald-200/80 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-900/60',
    ];

    $catStyle = $categoryColors[$task->category] ?? 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700';

    $hasSubtasks = $task->subtasks_count > 0;
    $progressPercent = $task->subtasks_progress;
@endphp

@section('content')
<div class="mx-auto max-w-7xl space-y-6 pb-12" id="task-details-view" data-task-id="{{ $task->id }}" data-task-status="{{ $task->status }}">

    {{-- =========================================================
         TOP BREADCRUMB & UTILITY NAVIGATION
    ========================================================== --}}
    <nav class="flex flex-wrap items-center justify-between gap-4" aria-label="Task Navigation">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('tasks.index') }}"
                class="group inline-flex items-center gap-2 rounded-xl border border-slate-200/80 bg-white px-3.5 py-2 font-semibold text-slate-700 shadow-xs transition hover:border-blue-500/30 hover:bg-blue-50/50 hover:text-blue-600 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white"
                title="Back to tasks (Esc)">
                <x-icon name="arrow-left" class="h-4 w-4 transition group-hover:-translate-x-0.5" />
                <span>Back to Tasks</span>
                <kbd class="hidden rounded border border-slate-200 bg-slate-100 px-1.5 py-0.5 text-[10px] font-mono text-slate-400 sm:inline dark:border-slate-700 dark:bg-slate-800 dark:text-slate-500">Esc</kbd>
            </a>

            <span class="text-slate-300 dark:text-slate-700">/</span>

            @if($task->category)
                <a href="{{ route('all-tasks', ['category' => $task->category]) }}"
                    class="rounded-lg border px-2.5 py-1 text-xs font-bold tracking-wide transition hover:opacity-80 {{ $catStyle }}">
                    {{ $task->category }}
                </a>
                <span class="text-slate-300 dark:text-slate-700">/</span>
            @endif

            <span class="max-w-[200px] truncate text-xs font-semibold text-slate-500 sm:max-w-xs dark:text-slate-400">
                #{{ $task->id }}
            </span>
        </div>

        {{-- Top Right Quick Actions --}}
        <div class="flex items-center gap-2">
            {{-- Pin Toggle Button --}}
            <button type="button"
                id="btn-detail-pin"
                data-detail-pin-task="{{ $task->id }}"
                class="inline-flex h-9 w-9 items-center justify-center rounded-xl border transition shadow-xs {{ $task->is_pinned ? 'border-amber-300 bg-amber-50 text-amber-600 dark:border-amber-800 dark:bg-amber-950/50 dark:text-amber-400' : 'border-slate-200 bg-white text-slate-500 hover:border-amber-300 hover:text-amber-500 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400 dark:hover:text-amber-400' }}"
                title="{{ $task->is_pinned ? 'Unpin Task' : 'Pin Task to Top' }}">
                <x-icon name="pin" class="h-4 w-4" />
            </button>

            {{-- Copy Link Button --}}
            <button type="button"
                id="btn-detail-copy"
                class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-slate-200/80 bg-white px-3 text-xs font-semibold text-slate-600 shadow-xs transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white"
                title="Copy link to task">
                <x-icon name="copy" class="h-3.5 w-3.5" />
                <span class="hidden sm:inline">Copy Link</span>
            </button>

            {{-- Edit Task Button (Triggers Edit Modal) --}}
            <a href="{{ route('tasks.edit', $task) }}"
                data-edit-task-id="{{ $task->id }}"
                class="inline-flex h-9 items-center gap-1.5 rounded-xl bg-blue-600 px-3.5 text-xs font-bold text-white shadow-sm shadow-blue-500/20 transition hover:bg-blue-700 active:scale-95"
                title="Edit Task Details (E)">
                <x-icon name="edit" class="h-3.5 w-3.5" />
                <span>Edit Task</span>
            </a>
        </div>
    </nav>


    {{-- =========================================================
         HERO CARD: STATUS, TITLE & LIVE SEGMENTED CONTROLLER
    ========================================================== --}}
    <section class="relative overflow-hidden rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm sm:p-8 dark:border-slate-800 dark:bg-slate-900">
        {{-- Background ambient aura glow --}}
        <div id="hero-aura-glow" class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-gradient-to-br {{ $status['glow'] }} blur-3xl transition-all duration-700"></div>

        <div class="relative space-y-5">
            {{-- Badges Header Row --}}
            <div class="flex flex-wrap items-center gap-2">
                {{-- Status Badge --}}
                <span id="hero-status-badge"
                    class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-bold tracking-wide {{ $status['badge'] }}">
                    <span id="hero-status-dot" class="h-2 w-2 rounded-full {{ $status['dot'] }} animate-pulse"></span>
                    <span id="hero-status-text">{{ $status['label'] }}</span>
                </span>

                {{-- Priority Badge --}}
                <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-bold tracking-wide {{ $priority['badge'] }}">
                    <x-icon name="flag" class="h-3.5 w-3.5 {{ $priority['accent'] }}" />
                    <span>{{ $priority['label'] }}</span>
                </span>

                {{-- Category Pill --}}
                @if($task->category)
                    <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-bold {{ $catStyle }}">
                        <x-icon name="layers" class="h-3 w-3" />
                        <span>{{ $task->category }}</span>
                    </span>
                @endif

                {{-- Overdue or Due Date Pill --}}
                @if($task->is_overdue)
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-bold text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/60 dark:text-rose-300">
                        <span class="h-2 w-2 rounded-full bg-rose-500 animate-ping"></span>
                        <span>Overdue (due {{ $task->due_date->format('M j') }})</span>
                    </span>
                @elseif($task->due_date && $task->due_date->isToday())
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700 dark:border-amber-900/60 dark:bg-amber-950/60 dark:text-amber-300">
                        <x-icon name="calendar" class="h-3.5 w-3.5 text-amber-500" />
                        <span>Due Today</span>
                    </span>
                @elseif($task->due_date)
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                        <x-icon name="calendar" class="h-3.5 w-3.5 text-slate-400" />
                        <span>Due {{ $task->due_date->format('M j, Y') }}</span>
                    </span>
                @endif

                {{-- Pinned indicator --}}
                <span id="hero-pinned-badge" class="{{ $task->is_pinned ? 'inline-flex' : 'hidden' }} items-center gap-1 rounded-full border border-amber-200 bg-amber-50/80 px-2.5 py-0.5 text-xs font-bold text-amber-700 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-300">
                    <x-icon name="pin" class="h-3 w-3 text-amber-500" />
                    <span>Pinned</span>
                </span>
            </div>

            {{-- Task Title --}}
            <h1 id="hero-task-title"
                class="break-words text-2xl font-black tracking-tight text-slate-950 sm:text-3xl lg:text-4xl dark:text-white transition-colors duration-200 {{ $task->status === 'completed' ? 'line-through text-slate-400 dark:text-slate-500' : '' }}">
                {{ $task->title }}
            </h1>

            {{-- 1-Click Interactive Segmented Status Switcher --}}
            <div class="pt-2">
                <p class="mb-2 text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Quick Status</p>
                <div class="inline-flex max-w-full flex-wrap items-center rounded-2xl border border-slate-200/80 bg-slate-100/80 p-1.5 dark:border-slate-800 dark:bg-slate-800/60" role="group" aria-label="Task Status Selector">
                    {{-- Pending Option --}}
                    <button type="button"
                        data-set-status="pending"
                        class="status-segment-btn flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-bold transition-all duration-200 {{ $task->status === 'pending' ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-900 dark:text-white ring-1 ring-slate-200/80 dark:ring-slate-700' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' }}">
                        <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                        <span>Pending</span>
                    </button>

                    {{-- In Progress Option --}}
                    <button type="button"
                        data-set-status="in_progress"
                        class="status-segment-btn flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-bold transition-all duration-200 {{ $task->status === 'in_progress' ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-900 dark:text-white ring-1 ring-slate-200/80 dark:ring-slate-700' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' }}">
                        <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                        <span>In Progress</span>
                    </button>

                    {{-- Completed Option --}}
                    <button type="button"
                        data-set-status="completed"
                        class="status-segment-btn flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-bold transition-all duration-200 {{ $task->status === 'completed' ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-900 dark:text-white ring-1 ring-slate-200/80 dark:ring-slate-700' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' }}">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        <span>Completed</span>
                    </button>
                </div>
            </div>
        </div>
    </section>


    {{-- =========================================================
         MAIN 2-COLUMN PRODUCTIVITY GRID
    ========================================================== --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">

        {{-- =====================================================
             LEFT COLUMN (8 cols): DESCRIPTION, CHECKLIST & NOVA
        ====================================================== --}}
        <div class="space-y-6 lg:col-span-8">

            {{-- 1. DESCRIPTION CARD --}}
            <section class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm sm:p-7 dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400">
                            <x-icon name="clipboard" class="h-4 w-4" />
                        </span>
                        <h2 class="text-base font-bold text-slate-950 dark:text-white">Description</h2>
                    </div>

                    <a href="{{ route('tasks.edit', $task) }}" data-edit-task-id="{{ $task->id }}" class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:underline dark:text-blue-400">
                        <x-icon name="edit" class="h-3 w-3" />
                        <span>Edit description</span>
                    </a>
                </div>

                <div class="mt-5">
                    @if($task->description)
                        <div class="whitespace-pre-wrap break-words text-sm leading-relaxed text-slate-700 dark:text-slate-200 sm:text-[15px]">
                            {{ $task->description }}
                        </div>
                    @else
                        <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/60 p-6 text-center dark:border-slate-800 dark:bg-slate-800/30">
                            <x-icon name="clipboard" class="mx-auto h-8 w-8 text-slate-400 dark:text-slate-600" />
                            <p class="mt-2 text-sm font-semibold text-slate-600 dark:text-slate-400">No description provided yet</p>
                            <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">Add detailed specs, links, or notes to keep your team aligned.</p>
                            <a href="{{ route('tasks.edit', $task) }}" data-edit-task-id="{{ $task->id }}" class="mt-3 inline-flex items-center gap-1.5 rounded-xl bg-slate-100 px-3.5 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                                <x-icon name="plus" class="h-3.5 w-3.5" />
                                <span>Add Description</span>
                            </a>
                        </div>
                    @endif
                </div>

                {{-- Tags Bar --}}
                @if($task->tags && count($task->tags) > 0)
                    <div class="mt-6 border-t border-slate-100 pt-5 dark:border-slate-800">
                        <div class="flex items-center gap-2 mb-3 text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                            <x-icon name="tag" class="h-3.5 w-3.5" />
                            <span>Tags</span>
                        </div>
                        <div class="flex flex-wrap gap-2" aria-label="Tags">
                            @foreach($task->tags as $tag)
                                <a href="{{ route('all-tasks', ['q' => $tag]) }}"
                                    class="group inline-flex items-center gap-1 rounded-xl border border-slate-200/80 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-700 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700 dark:border-slate-700/80 dark:bg-slate-800/80 dark:text-slate-300 dark:hover:border-blue-800 dark:hover:bg-blue-950/60 dark:hover:text-blue-300"
                                    title="Filter tasks with tag {{ $tag }}">
                                    <span class="text-blue-500 dark:text-blue-400">#</span>
                                    <span>{{ $tag }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>


            {{-- 2. CHECKLIST & SUBTASKS (THE CROWN JEWEL) --}}
            <section class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm sm:p-7 dark:border-slate-800 dark:bg-slate-900" id="checklist-card">
                {{-- Header with Progress & AI Breakdown Trigger --}}
                <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 pb-5 dark:border-slate-800">
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                            <x-icon name="check-circle" class="h-5 w-5" />
                        </span>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-base font-bold text-slate-950 dark:text-white">Checklist</h2>
                                <span id="checklist-counter-badge" class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                    <span id="detail-completed-count">{{ $task->completed_subtasks_count }}</span>/<span id="detail-total-count">{{ $task->subtasks_count }}</span>
                                </span>
                            </div>
                            <p class="mt-0.5 text-xs text-slate-400 dark:text-slate-500">Break work down into manageable, actionable milestones.</p>
                        </div>
                    </div>

                    {{-- AI Magic Breakdown Button --}}
                    <button type="button"
                        id="btn-detail-ai-breakdown"
                        data-ai-detail-breakdown="{{ $task->id }}"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-purple-200 bg-purple-50/80 px-3.5 py-2 text-xs font-bold text-purple-700 shadow-xs transition hover:border-purple-300 hover:bg-purple-100 dark:border-purple-900/60 dark:bg-purple-950/50 dark:text-purple-300 dark:hover:bg-purple-900/60"
                        title="Generate smart subtasks using Nova AI">
                        <x-icon name="sparkles" class="h-3.5 w-3.5 text-purple-600 dark:text-purple-400" />
                        <span>✨ AI Breakdown</span>
                    </button>
                </div>

                {{-- Animated Progress Bar --}}
                <div class="mt-5 space-y-1.5" id="checklist-progress-container">
                    <div class="flex items-center justify-between text-xs font-bold text-slate-500 dark:text-slate-400">
                        <span>Progress</span>
                        <span id="detail-progress-percent" class="text-emerald-600 dark:text-emerald-400 font-extrabold">{{ $progressPercent }}%</span>
                    </div>
                    <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                        <div id="detail-progress-bar"
                            class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-teal-400 transition-all duration-500"
                            style="width: {{ $progressPercent }}%"></div>
                    </div>
                </div>

                {{-- Subtasks Items List --}}
                <div class="mt-6 space-y-2.5" id="detail-subtasks-list">
                    @forelse($task->subtasks ?? [] as $subtask)
                        @php
                            $stId = $subtask['id'] ?? (string) \Illuminate\Support\Str::uuid();
                            $isDone = !empty($subtask['completed']);
                        @endphp
                        <div class="group flex items-center justify-between gap-3 rounded-2xl border border-slate-100 bg-slate-50/60 p-3.5 transition hover:border-slate-200 hover:bg-white hover:shadow-xs dark:border-slate-800/80 dark:bg-slate-800/40 dark:hover:border-slate-700 dark:hover:bg-slate-800"
                            data-subtask-item-id="{{ $stId }}">
                            <label class="flex min-w-0 flex-1 cursor-pointer items-center gap-3.5">
                                <input type="checkbox"
                                    data-task-subtask-toggle="{{ $task->id }}"
                                    data-subtask-id="{{ $stId }}"
                                    {{ $isDone ? 'checked' : '' }}
                                    class="h-4.5 w-4.5 rounded-md border-slate-300 text-emerald-600 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-700 transition cursor-pointer">
                                <span class="subtask-title-text break-words text-sm font-semibold transition-colors duration-200 {{ $isDone ? 'line-through text-slate-400 dark:text-slate-500' : 'text-slate-800 dark:text-slate-200' }}">
                                    {{ $subtask['title'] }}
                                </span>
                            </label>

                            <button type="button"
                                data-delete-subtask-id="{{ $stId }}"
                                class="opacity-0 group-hover:opacity-100 rounded-lg p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40 dark:hover:text-rose-400 transition"
                                title="Delete checklist item">
                                <x-icon name="trash" class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    @empty
                        <div id="checklist-empty-state" class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/50 p-6 text-center dark:border-slate-800 dark:bg-slate-800/20">
                            <span class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500">
                                <x-icon name="check-circle" class="h-5 w-5" />
                            </span>
                            <p class="mt-2 text-sm font-bold text-slate-700 dark:text-slate-300">No checklist items yet</p>
                            <p class="mt-1 text-xs text-slate-400">Add milestones below or click AI Breakdown to generate smart steps automatically.</p>
                        </div>
                    @endforelse
                </div>

                {{-- Inline Add New Subtask Bar --}}
                <form id="form-add-subtask" class="mt-5 flex gap-2">
                    @csrf
                    <input type="text"
                        id="input-new-subtask"
                        placeholder="Add a new checklist step... (Press Enter)"
                        class="h-11 flex-1 rounded-xl border border-slate-200 bg-slate-50/80 px-4 text-sm text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:placeholder:text-slate-500 dark:focus:ring-blue-900/40"
                        required>
                    <button type="submit"
                        class="inline-flex h-11 items-center gap-1.5 rounded-xl bg-blue-600 px-4 text-xs font-bold text-white shadow-xs transition hover:bg-blue-700 active:scale-95">
                        <x-icon name="plus" class="h-4 w-4" />
                        <span class="hidden sm:inline">Add Step</span>
                    </button>
                </form>
            </section>


            {{-- 3. NOVA AI COPILOT ASSISTANT CARD --}}
            <section class="relative overflow-hidden rounded-3xl border border-blue-500/20 bg-gradient-to-br from-blue-50/60 via-indigo-50/30 to-purple-50/40 p-6 shadow-xs dark:border-blue-500/20 dark:from-blue-950/20 dark:via-indigo-950/15 dark:to-purple-950/20">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex items-start gap-3.5">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/20">
                            <x-icon name="bot" class="h-5 w-5" />
                        </span>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-bold text-slate-950 dark:text-white">Nova AI Copilot</h3>
                                <span class="rounded-md bg-blue-100 px-1.5 py-0.5 text-[10px] font-black uppercase tracking-wider text-blue-700 dark:bg-blue-900/60 dark:text-blue-300">Active</span>
                            </div>
                            <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">
                                Need help organizing, estimating, or executing "{{ $task->title }}"? Nova can provide strategic advice or code assistance.
                            </p>
                        </div>
                    </div>

                    <button type="button"
                        data-chat-prompt="Help me plan and execute the task '{{ addslashes($task->title) }}'. Here is the description: '{{ addslashes($task->description ?? '') }}'. What are the best best practices and step-by-step approach?"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-white px-3.5 py-2 text-xs font-bold text-blue-700 shadow-xs transition hover:bg-blue-50 hover:shadow-sm dark:bg-slate-800 dark:text-blue-300 dark:hover:bg-slate-700">
                        <x-icon name="sparkles" class="h-3.5 w-3.5 text-blue-600 dark:text-blue-400" />
                        <span>Chat with Nova</span>
                    </button>
                </div>

                {{-- Nova Quick Prompts --}}
                <div class="mt-4 flex flex-wrap gap-2 pt-2 border-t border-blue-100/80 dark:border-blue-900/40">
                    <button type="button"
                        data-chat-prompt="How can I test and QA the task '{{ addslashes($task->title) }}' thoroughly? Provide a checklist of edge cases."
                        class="ai-quick-btn inline-flex items-center gap-1 rounded-lg border border-white/80 bg-white/70 px-2.5 py-1 text-[11px] font-semibold text-slate-700 transition hover:bg-white hover:text-blue-600 dark:border-slate-800 dark:bg-slate-800/60 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-blue-300">
                        <span>🧪 QA & Edge Cases</span>
                    </button>
                    <button type="button"
                        data-chat-prompt="Suggest a realistic timeline, milestones, and estimate for '{{ addslashes($task->title) }}'."
                        class="ai-quick-btn inline-flex items-center gap-1 rounded-lg border border-white/80 bg-white/70 px-2.5 py-1 text-[11px] font-semibold text-slate-700 transition hover:bg-white hover:text-blue-600 dark:border-slate-800 dark:bg-slate-800/60 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-blue-300">
                        <span>⏱ Estimate Time</span>
                    </button>
                    <button type="button"
                        data-chat-prompt="Draft a release note and documentation summary for completed work on '{{ addslashes($task->title) }}'."
                        class="ai-quick-btn inline-flex items-center gap-1 rounded-lg border border-white/80 bg-white/70 px-2.5 py-1 text-[11px] font-semibold text-slate-700 transition hover:bg-white hover:text-blue-600 dark:border-slate-800 dark:bg-slate-800/60 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-blue-300">
                        <span>📝 Release Notes</span>
                    </button>
                </div>
            </section>
        </div>


        {{-- =====================================================
             RIGHT COLUMN (4 cols): PROPERTIES INSPECTOR & ACTIONS
        ====================================================== --}}
        <div class="space-y-6 lg:col-span-4">

            {{-- 1. PRIMARY ACTIONS WIDGET --}}
            <section class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 space-y-3">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Actions</p>

                {{-- Hero Complete/Reopen Toggle Button --}}
                <button type="button"
                    id="btn-hero-toggle-complete"
                    data-detail-toggle-complete="{{ $task->id }}"
                    class="w-full flex items-center justify-center gap-2 rounded-2xl py-3.5 px-4 text-sm font-extrabold shadow-md transition-all duration-200 active:scale-98 {{ $task->status === 'completed' ? 'bg-slate-100 text-slate-800 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700' : 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-emerald-500/20 hover:from-emerald-500 hover:to-teal-500' }}">
                    <x-icon name="{{ $task->status === 'completed' ? 'refresh-cw' : 'check' }}" class="h-4.5 w-4.5" />
                    <span id="hero-complete-btn-text">{{ $task->status === 'completed' ? 'Reopen Task' : 'Mark as Complete' }}</span>
                </button>

                <div class="grid grid-cols-2 gap-2 pt-1">
                    {{-- Edit Task Button --}}
                    <a href="{{ route('tasks.edit', $task) }}"
                        data-edit-task-id="{{ $task->id }}"
                        class="flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white py-2.5 px-3 text-xs font-bold text-slate-700 transition hover:bg-slate-50 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-750">
                        <x-icon name="edit" class="h-3.5 w-3.5" />
                        <span>Edit</span>
                    </a>

                    {{-- Pin Toggle Button --}}
                    <button type="button"
                        data-detail-pin-task="{{ $task->id }}"
                        class="detail-pin-btn flex items-center justify-center gap-1.5 rounded-xl border py-2.5 px-3 text-xs font-bold transition {{ $task->is_pinned ? 'border-amber-300 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-300' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200' }}">
                        <x-icon name="pin" class="h-3.5 w-3.5 text-amber-500" />
                        <span class="detail-pin-text">{{ $task->is_pinned ? 'Unpin' : 'Pin' }}</span>
                    </button>
                </div>

                {{-- Delete Task Form --}}
                <div class="pt-2 border-t border-slate-100 dark:border-slate-800">
                    <form method="POST" action="{{ route('tasks.destroy', $task) }}" data-confirm="Delete this task?">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="w-full flex items-center justify-center gap-1.5 rounded-xl py-2 px-3 text-xs font-bold text-rose-500 transition hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40">
                            <x-icon name="trash" class="h-3.5 w-3.5" />
                            <span>Delete Task</span>
                        </button>
                    </form>
                </div>
            </section>


            {{-- 2. PROPERTIES INSPECTOR (LINEAR STYLE) --}}
            <section class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6 dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3.5 dark:border-slate-800">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Properties</h3>
                    <span class="text-[11px] font-semibold text-slate-400 font-mono">#{{ $task->id }}</span>
                </div>

                <div class="divide-y divide-slate-100 text-xs dark:divide-slate-800">
                    {{-- Status Row --}}
                    <div class="flex items-center justify-between py-3">
                        <span class="flex items-center gap-2 font-medium text-slate-500 dark:text-slate-400">
                            <x-icon name="circle" class="h-3.5 w-3.5" />
                            <span>Status</span>
                        </span>
                        <span id="prop-status-label" class="font-bold text-slate-900 dark:text-white capitalize">
                            {{ str_replace('_', ' ', $task->status) }}
                        </span>
                    </div>

                    {{-- Priority Row --}}
                    <div class="flex items-center justify-between py-3">
                        <span class="flex items-center gap-2 font-medium text-slate-500 dark:text-slate-400">
                            <x-icon name="flag" class="h-3.5 w-3.5" />
                            <span>Priority</span>
                        </span>
                        <span class="inline-flex items-center gap-1.5 font-bold {{ $priority['accent'] }}">
                            <span class="h-1.5 w-1.5 rounded-full {{ $priority['dot'] }}"></span>
                            <span>{{ $priority['label'] }}</span>
                        </span>
                    </div>

                    {{-- Category Row --}}
                    <div class="flex items-center justify-between py-3">
                        <span class="flex items-center gap-2 font-medium text-slate-500 dark:text-slate-400">
                            <x-icon name="layers" class="h-3.5 w-3.5" />
                            <span>Category</span>
                        </span>
                        @if($task->category)
                            <a href="{{ route('all-tasks', ['category' => $task->category]) }}"
                                class="rounded-lg border px-2 py-0.5 font-bold hover:underline {{ $catStyle }}">
                                {{ $task->category }}
                            </a>
                        @else
                            <span class="text-slate-400">General</span>
                        @endif
                    </div>

                    {{-- Due Date Row --}}
                    <div class="flex items-center justify-between py-3">
                        <span class="flex items-center gap-2 font-medium text-slate-500 dark:text-slate-400">
                            <x-icon name="calendar" class="h-3.5 w-3.5" />
                            <span>Due Date</span>
                        </span>
                        <div class="text-right">
                            <p class="font-bold text-slate-900 dark:text-white">
                                {{ $task->due_date ? $task->due_date->format('M j, Y') : 'No due date' }}
                            </p>
                            @if($task->due_date)
                                <p class="text-[10px] text-slate-400">{{ $task->due_date->diffForHumans() }}</p>
                            @endif
                        </div>
                    </div>

                    {{-- End Date Row --}}
                    <div class="flex items-center justify-between py-3">
                        <span class="flex items-center gap-2 font-medium text-slate-500 dark:text-slate-400">
                            <x-icon name="clock" class="h-3.5 w-3.5" />
                            <span>End Date</span>
                        </span>
                        <p class="font-bold text-slate-900 dark:text-white">
                            {{ $task->end_date ? $task->end_date->format('M j, Y') : 'No end date' }}
                        </p>
                    </div>

                    {{-- Updated Row --}}
                    <div class="flex items-center justify-between py-3">
                        <span class="flex items-center gap-2 font-medium text-slate-500 dark:text-slate-400">
                            <x-icon name="activity" class="h-3.5 w-3.5" />
                            <span>Updated</span>
                        </span>
                        <p id="prop-updated-text" class="font-bold text-slate-900 dark:text-white">
                            {{ $task->updated_at->diffForHumans() }}
                        </p>
                    </div>

                    {{-- Created Row --}}
                    <div class="flex items-center justify-between py-3">
                        <span class="flex items-center gap-2 font-medium text-slate-500 dark:text-slate-400">
                            <x-icon name="task-logo" class="h-3.5 w-3.5" />
                            <span>Created</span>
                        </span>
                        <p class="font-medium text-slate-500 dark:text-slate-400">
                            {{ $task->created_at->format('M j, Y') }}
                        </p>
                    </div>
                </div>
            </section>


            {{-- 3. TIMELINE & MILESTONES CARD --}}
            <section class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h3 class="mb-4 text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Timeline</h3>

                <div class="relative pl-6 space-y-5 before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200 dark:before:bg-slate-800">
                    {{-- Created step --}}
                    <div class="relative">
                        <span class="absolute -left-6 top-1 h-3.5 w-3.5 rounded-full border-2 border-white bg-blue-600 dark:border-slate-900"></span>
                        <p class="text-xs font-bold text-slate-900 dark:text-white">Created</p>
                        <p class="text-[11px] text-slate-400">{{ $task->created_at->format('M j, Y - g:i A') }}</p>
                    </div>

                    {{-- Last Activity step --}}
                    <div class="relative">
                        <span class="absolute -left-6 top-1 h-3.5 w-3.5 rounded-full border-2 border-white bg-indigo-500 dark:border-slate-900"></span>
                        <p class="text-xs font-bold text-slate-900 dark:text-white">Last Activity</p>
                        <p class="text-[11px] text-slate-400">{{ $task->updated_at->diffForHumans() }}</p>
                    </div>

                    {{-- Deadline milestone --}}
                    <div class="relative">
                        <span class="absolute -left-6 top-1 h-3.5 w-3.5 rounded-full border-2 border-white {{ $task->is_overdue ? 'bg-rose-500' : 'bg-emerald-500' }} dark:border-slate-900"></span>
                        <p class="text-xs font-bold text-slate-900 dark:text-white">Deadline</p>
                        <p class="text-[11px] text-slate-400">
                            {{ $task->due_date ? $task->due_date->format('M j, Y') : 'Flexible timeline' }}
                        </p>
                    </div>
                </div>
            </section>
        </div>

    </div>
</div>

{{-- Inline Interactive JavaScript for Task Details --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
    const taskId = '{{ $task->id }}';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // =========================================================
    // 1. ESCAPE KEY TO GO BACK TO TASKS
    // =========================================================
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !document.querySelector('.swal2-container') && !document.querySelector('#task-modal:not(.hidden)')) {
            window.location.assign('{{ route("tasks.index") }}');
        }
    });

    // =========================================================
    // 2. COPY LINK TO CLIPBOARD
    // =========================================================
    const copyBtn = document.getElementById('btn-detail-copy');
    copyBtn?.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(window.location.href);
            copyBtn.classList.add('text-emerald-600', 'border-emerald-300');
            const originalHtml = copyBtn.innerHTML;
            copyBtn.innerHTML = `
                <svg class="h-3.5 w-3.5 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                <span class="hidden sm:inline text-emerald-600">Copied!</span>
            `;
            if (typeof showToast === 'function') {
                showToast('success', 'Task link copied to clipboard!');
            }
            setTimeout(() => {
                copyBtn.classList.remove('text-emerald-600', 'border-emerald-300');
                copyBtn.innerHTML = originalHtml;
            }, 2000);
        } catch (_err) {
            // fallback
        }
    });

    // =========================================================
    // 3. LIVE 1-CLICK STATUS SWITCHER
    // =========================================================
    const statusBtns = document.querySelectorAll('[data-set-status]');
    const heroStatusBadge = document.getElementById('hero-status-badge');
    const heroStatusDot = document.getElementById('hero-status-dot');
    const heroStatusText = document.getElementById('hero-status-text');
    const heroTaskTitle = document.getElementById('hero-task-title');
    const heroCompleteBtn = document.getElementById('btn-hero-toggle-complete');
    const heroCompleteText = document.getElementById('hero-complete-btn-text');
    const propStatusLabel = document.getElementById('prop-status-label');
    const heroAuraGlow = document.getElementById('hero-aura-glow');

    async function applyStatusUpdate(newStatus) {
        try {
            const res = await fetch(`/tasks/${taskId}/toggle-status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ status: newStatus }),
            });
            const data = await res.json();
            if (data.success) {
                const isCompleted = data.status === 'completed';

                // Update segmented controller UI
                statusBtns.forEach(btn => {
                    const active = btn.dataset.setStatus === data.status;
                    btn.className = `status-segment-btn flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-bold transition-all duration-200 ${
                        active
                            ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-900 dark:text-white ring-1 ring-slate-200/80 dark:ring-slate-700'
                            : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'
                    }`;
                });

                // Update badge colors & text
                if (data.status === 'completed') {
                    heroStatusBadge.className = 'inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-bold tracking-wide bg-emerald-50 text-emerald-700 border-emerald-200/80 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-900/60';
                    heroStatusDot.className = 'h-2 w-2 rounded-full bg-emerald-500 animate-pulse';
                    heroStatusText.textContent = 'Completed';
                    heroTaskTitle.classList.add('line-through', 'text-slate-400', 'dark:text-slate-500');
                    heroCompleteBtn.className = 'w-full flex items-center justify-center gap-2 rounded-2xl py-3.5 px-4 text-sm font-extrabold shadow-md transition-all duration-200 active:scale-98 bg-slate-100 text-slate-800 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700';
                    heroCompleteText.textContent = 'Reopen Task';
                    if (heroAuraGlow) heroAuraGlow.className = 'pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-gradient-to-br from-emerald-500/10 via-transparent to-transparent blur-3xl transition-all duration-700';

                    if (typeof playTaskChime === 'function') playTaskChime();
                    if (typeof fireConfetti === 'function') fireConfetti();
                    if (typeof showToast === 'function') showToast('success', 'Task marked as completed! 🎉');
                } else if (data.status === 'in_progress') {
                    heroStatusBadge.className = 'inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-bold tracking-wide bg-blue-50 text-blue-700 border-blue-200/80 dark:bg-blue-950/50 dark:text-blue-300 dark:border-blue-900/60';
                    heroStatusDot.className = 'h-2 w-2 rounded-full bg-blue-500 animate-pulse';
                    heroStatusText.textContent = 'In Progress';
                    heroTaskTitle.classList.remove('line-through', 'text-slate-400', 'dark:text-slate-500');
                    heroCompleteBtn.className = 'w-full flex items-center justify-center gap-2 rounded-2xl py-3.5 px-4 text-sm font-extrabold shadow-md transition-all duration-200 active:scale-98 bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-emerald-500/20 hover:from-emerald-500 hover:to-teal-500';
                    heroCompleteText.textContent = 'Mark as Complete';
                    if (heroAuraGlow) heroAuraGlow.className = 'pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-gradient-to-br from-blue-500/10 via-transparent to-transparent blur-3xl transition-all duration-700';
                    if (typeof showToast === 'function') showToast('info', 'Task set to In Progress');
                } else {
                    heroStatusBadge.className = 'inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-bold tracking-wide bg-amber-50 text-amber-700 border-amber-200/80 dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-900/60';
                    heroStatusDot.className = 'h-2 w-2 rounded-full bg-amber-500 animate-pulse';
                    heroStatusText.textContent = 'Pending';
                    heroTaskTitle.classList.remove('line-through', 'text-slate-400', 'dark:text-slate-500');
                    heroCompleteBtn.className = 'w-full flex items-center justify-center gap-2 rounded-2xl py-3.5 px-4 text-sm font-extrabold shadow-md transition-all duration-200 active:scale-98 bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-emerald-500/20 hover:from-emerald-500 hover:to-teal-500';
                    heroCompleteText.textContent = 'Mark as Complete';
                    if (heroAuraGlow) heroAuraGlow.className = 'pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-gradient-to-br from-amber-500/10 via-transparent to-transparent blur-3xl transition-all duration-700';
                    if (typeof showToast === 'function') showToast('info', 'Task reopened to Pending');
                }

                if (propStatusLabel) propStatusLabel.textContent = data.status.replace('_', ' ');
            }
        } catch (_err) {
            if (typeof showToast === 'function') showToast('error', 'Failed to update status');
        }
    }

    statusBtns.forEach(btn => {
        btn.addEventListener('click', () => applyStatusUpdate(btn.dataset.setStatus));
    });

    heroCompleteBtn?.addEventListener('click', () => {
        const currentIsCompleted = heroTaskTitle.classList.contains('line-through');
        applyStatusUpdate(currentIsCompleted ? 'pending' : 'completed');
    });

    // =========================================================
    // 4. PIN / UNPIN TOGGLE
    // =========================================================
    const pinBtns = document.querySelectorAll('[data-detail-pin-task]');
    pinBtns.forEach(btn => {
        btn.addEventListener('click', async () => {
            try {
                const res = await fetch(`/tasks/${taskId}/toggle-pin`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });
                const data = await res.json();
                if (data.success) {
                    const isPinned = data.is_pinned;
                    const heroPinnedBadge = document.getElementById('hero-pinned-badge');
                    if (heroPinnedBadge) heroPinnedBadge.classList.toggle('hidden', !isPinned);

                    document.querySelectorAll('.detail-pin-text').forEach(t => t.textContent = isPinned ? 'Unpin' : 'Pin');
                    document.getElementById('btn-detail-pin')?.classList.toggle('border-amber-300', isPinned);
                    document.getElementById('btn-detail-pin')?.classList.toggle('bg-amber-50', isPinned);
                    document.getElementById('btn-detail-pin')?.classList.toggle('text-amber-600', isPinned);

                    if (typeof showToast === 'function') showToast('success', isPinned ? 'Task pinned to top' : 'Task unpinned');
                }
            } catch (_err) {}
        });
    });

    // =========================================================
    // 5. CHECKLIST PROGRESS RE-CALCULATOR
    // =========================================================
    function updateChecklistProgress(progress, completed, total) {
        const progressPercent = document.getElementById('detail-progress-percent');
        const progressBar = document.getElementById('detail-progress-bar');
        const completedCount = document.getElementById('detail-completed-count');
        const totalCount = document.getElementById('detail-total-count');

        if (progressPercent) progressPercent.textContent = `${progress}%`;
        if (progressBar) progressBar.style.width = `${progress}%`;
        if (completedCount) completedCount.textContent = completed;
        if (totalCount) totalCount.textContent = total;

        if (progress === 100 && total > 0) {
            if (typeof fireConfetti === 'function') fireConfetti();
            if (typeof playTaskChime === 'function') playTaskChime();
        }
    }

    // =========================================================
    // 6. INLINE CHECKLIST TOGGLE & DELETE
    // =========================================================
    const subtasksList = document.getElementById('detail-subtasks-list');

    subtasksList?.addEventListener('change', async (e) => {
        const checkbox = e.target.closest('[data-task-subtask-toggle]');
        if (!checkbox) return;

        const subtaskId = checkbox.dataset.subtaskId;
        const textSpan = checkbox.closest('label')?.querySelector('.subtask-title-text');

        try {
            const res = await fetch(`/tasks/${taskId}/subtasks/${subtaskId}/toggle`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });
            const data = await res.json();
            if (data.success) {
                if (textSpan) {
                    textSpan.classList.toggle('line-through', checkbox.checked);
                    textSpan.classList.toggle('text-slate-400', checkbox.checked);
                    textSpan.classList.toggle('dark:text-slate-500', checkbox.checked);
                    textSpan.classList.toggle('text-slate-800', !checkbox.checked);
                    textSpan.classList.toggle('dark:text-slate-200', !checkbox.checked);
                }
                updateChecklistProgress(data.progress, data.completed_count, data.total_count);
            }
        } catch (_err) {}
    });

    subtasksList?.addEventListener('click', async (e) => {
        const delBtn = e.target.closest('[data-delete-subtask-id]');
        if (!delBtn) return;

        const subtaskId = delBtn.dataset.deleteSubtaskId;
        const itemContainer = delBtn.closest('[data-subtask-item-id]');

        try {
            const res = await fetch(`/tasks/${taskId}/subtasks/${subtaskId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });
            const data = await res.json();
            if (data.success) {
                itemContainer?.remove();
                updateChecklistProgress(data.progress, data.completed_count, data.total_count);
                if (data.total_count === 0) {
                    const emptyState = document.createElement('div');
                    emptyState.id = 'checklist-empty-state';
                    emptyState.className = 'rounded-2xl border border-dashed border-slate-200 bg-slate-50/50 p-6 text-center dark:border-slate-800 dark:bg-slate-800/20';
                    emptyState.innerHTML = `
                        <span class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>
                        </span>
                        <p class="mt-2 text-sm font-bold text-slate-700 dark:text-slate-300">No checklist items yet</p>
                        <p class="mt-1 text-xs text-slate-400">Add milestones below or click AI Breakdown to generate smart steps automatically.</p>
                    `;
                    subtasksList.appendChild(emptyState);
                }
            }
        } catch (_err) {}
    });

    // =========================================================
    // 7. INLINE ADD SUBTASK FORM
    // =========================================================
    const addSubtaskForm = document.getElementById('form-add-subtask');
    const newSubtaskInput = document.getElementById('input-new-subtask');

    addSubtaskForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const title = newSubtaskInput.value.trim();
        if (!title) return;

        try {
            const res = await fetch(`/tasks/${taskId}/subtasks`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ title }),
            });
            const data = await res.json();
            if (data.success && data.subtask) {
                newSubtaskInput.value = '';
                document.getElementById('checklist-empty-state')?.remove();

                const item = document.createElement('div');
                item.className = 'group flex items-center justify-between gap-3 rounded-2xl border border-slate-100 bg-slate-50/60 p-3.5 transition hover:border-slate-200 hover:bg-white hover:shadow-xs dark:border-slate-800/80 dark:bg-slate-800/40 dark:hover:border-slate-700 dark:hover:bg-slate-800 animate-fadeIn';
                item.dataset.subtaskItemId = data.subtask.id;
                item.innerHTML = `
                    <label class="flex min-w-0 flex-1 cursor-pointer items-center gap-3.5">
                        <input type="checkbox"
                            data-task-subtask-toggle="${taskId}"
                            data-subtask-id="${data.subtask.id}"
                            class="h-4.5 w-4.5 rounded-md border-slate-300 text-emerald-600 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-700 transition cursor-pointer">
                        <span class="subtask-title-text break-words text-sm font-semibold transition-colors duration-200 text-slate-800 dark:text-slate-200">
                            ${escapeHtml(data.subtask.title)}
                        </span>
                    </label>
                    <button type="button"
                        data-delete-subtask-id="${data.subtask.id}"
                        class="opacity-0 group-hover:opacity-100 rounded-lg p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40 dark:hover:text-rose-400 transition"
                        title="Delete checklist item">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M10 11v6M14 11v6"/></svg>
                    </button>
                `;
                subtasksList.appendChild(item);
                updateChecklistProgress(data.progress, data.completed_count, data.total_count);
            }
        } catch (_err) {}
    });

    // =========================================================
    // 8. DETAIL AI BREAKDOWN BUTTON
    // =========================================================
    const aiDetailBtn = document.getElementById('btn-detail-ai-breakdown');
    aiDetailBtn?.addEventListener('click', async () => {
        if (aiDetailBtn.dataset.loading === 'true') return;
        aiDetailBtn.dataset.loading = 'true';
        const originalHtml = aiDetailBtn.innerHTML;
        aiDetailBtn.innerHTML = `
            <svg class="h-3.5 w-3.5 animate-spin text-purple-600 dark:text-purple-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10" stroke-opacity="0.25"/>
                <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"/>
            </svg>
            <span class="text-xs font-bold text-purple-700 dark:text-purple-300">Nova thinking...</span>
        `;

        try {
            const res = await fetch(`/tasks/${taskId}/ai-breakdown`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ lang: '{{ app()->getLocale() }}' }),
            });

            if (!res.ok) throw new Error('AI breakdown request failed');
            const data = await res.json();

            if (data.success && Array.isArray(data.subtasks)) {
                if (typeof playTaskChime === 'function') playTaskChime();
                if (typeof fireConfetti === 'function') fireConfetti();

                document.getElementById('checklist-empty-state')?.remove();
                subtasksList.innerHTML = data.subtasks.map(st => `
                    <div class="group flex items-center justify-between gap-3 rounded-2xl border border-slate-100 bg-slate-50/60 p-3.5 transition hover:border-slate-200 hover:bg-white hover:shadow-xs dark:border-slate-800/80 dark:bg-slate-800/40 dark:hover:border-slate-700 dark:hover:bg-slate-800 animate-fadeIn"
                        data-subtask-item-id="${st.id}">
                        <label class="flex min-w-0 flex-1 cursor-pointer items-center gap-3.5">
                            <input type="checkbox"
                                data-task-subtask-toggle="${taskId}"
                                data-subtask-id="${st.id}"
                                ${st.completed ? 'checked' : ''}
                                class="h-4.5 w-4.5 rounded-md border-slate-300 text-emerald-600 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-700 transition cursor-pointer">
                            <span class="subtask-title-text break-words text-sm font-semibold transition-colors duration-200 ${st.completed ? 'line-through text-slate-400 dark:text-slate-500' : 'text-slate-800 dark:text-slate-200'}">
                                ${escapeHtml(st.title)}
                            </span>
                        </label>
                        <button type="button"
                            data-delete-subtask-id="${st.id}"
                            class="opacity-0 group-hover:opacity-100 rounded-lg p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40 dark:hover:text-rose-400 transition"
                            title="Delete checklist item">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M10 11v6M14 11v6"/></svg>
                        </button>
                    </div>
                `).join('');

                updateChecklistProgress(data.subtasks_progress, data.completed_count, data.subtasks_count);
                if (typeof showToast === 'function') showToast('success', 'Nova generated smart checklist steps!');
            }
        } catch (err) {
            if (typeof showToast === 'function') showToast('error', 'Could not generate AI subtasks');
        } finally {
            aiDetailBtn.dataset.loading = 'false';
            aiDetailBtn.innerHTML = originalHtml;
        }
    });

    function escapeHtml(str) {
        return (str || '').replace(/[&<>"']/g, (m) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;',
        }[m]));
    }
});
</script>
@endsection
