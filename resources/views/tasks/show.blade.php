@extends('layouts.app')

@section('title', $task->title . ' - WorkMind')

@php
    $priorityConfig = [
        'high' => [
            'label' => 'High Priority',
            'short' => 'High',
            'badge' => 'bg-rose-50 text-rose-700 border-rose-200/80 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-900/60',
            'dot' => 'bg-rose-500',
            'accent' => 'text-rose-600 dark:text-rose-400',
        ],
        'medium' => [
            'label' => 'Medium Priority',
            'short' => 'Medium',
            'badge' => 'bg-amber-50 text-amber-700 border-amber-200/80 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-900/60',
            'dot' => 'bg-amber-500',
            'accent' => 'text-amber-600 dark:text-amber-400',
        ],
        'low' => [
            'label' => 'Low Priority',
            'short' => 'Low',
            'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200/80 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-900/60',
            'dot' => 'bg-emerald-500',
            'accent' => 'text-emerald-600 dark:text-emerald-400',
        ],
    ];

    $priority = $priorityConfig[$task->priority] ?? $priorityConfig['medium'];

    $statusConfig = [
        'pending' => [
            'label' => 'Pending',
            'badge' => 'bg-amber-50 text-amber-700 border-amber-200/80 dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-900/60',
            'dot' => 'bg-amber-500',
            'glow' => 'from-amber-400/10 via-transparent to-transparent',
        ],
        'in_progress' => [
            'label' => 'In Progress',
            'badge' => 'bg-blue-50 text-blue-700 border-blue-200/80 dark:bg-blue-950/50 dark:text-blue-300 dark:border-blue-900/60',
            'dot' => 'bg-blue-500',
            'glow' => 'from-blue-400/10 via-transparent to-transparent',
        ],
        'completed' => [
            'label' => 'Completed',
            'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200/80 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-900/60',
            'dot' => 'bg-emerald-500',
            'glow' => 'from-emerald-400/10 via-transparent to-transparent',
        ],
    ];

    $status = $statusConfig[$task->status] ?? $statusConfig['pending'];

    $categoryColors = [
        'Work' => 'bg-purple-50 text-purple-700 border-purple-200/80 dark:bg-purple-950/50 dark:text-purple-300 dark:border-purple-900/60',
        'Personal' => 'bg-indigo-50 text-indigo-700 border-indigo-200/80 dark:bg-indigo-950/50 dark:text-indigo-300 dark:border-indigo-900/60',
        'Urgent' => 'bg-rose-50 text-rose-700 border-rose-200/80 dark:bg-rose-950/50 dark:text-rose-300 dark:border-rose-900/60',
        'Design' => 'bg-pink-50 text-pink-700 border-pink-200/80 dark:bg-pink-950/50 dark:text-pink-300 dark:border-pink-900/60',
        'Dev' => 'bg-sky-50 text-sky-700 border-sky-200/80 dark:bg-sky-950/50 dark:text-sky-300 dark:border-sky-900/60',
        'Study' => 'bg-teal-50 text-teal-700 border-teal-200/80 dark:bg-teal-950/50 dark:text-teal-300 dark:border-teal-900/60',
        'Finance' => 'bg-emerald-50 text-emerald-700 border-emerald-200/80 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-900/60',
    ];

    $catStyle = $categoryColors[$task->category] ?? 'bg-purple-50 text-purple-700 border-purple-200/80 dark:bg-purple-950/50 dark:text-purple-300 dark:border-purple-900/60';

    $hasSubtasks = $task->subtasks_count > 0;
    $progressPercent = $task->subtasks_progress;

    // Colorful icon styles for subtask checklist items
    $subtaskIconPalette = [
        ['icon' => 'book', 'style' => 'bg-blue-50 text-blue-600 border border-blue-100/80 dark:bg-blue-950/50 dark:text-blue-300 dark:border-blue-900/50'],
        ['icon' => 'layers', 'style' => 'bg-purple-50 text-purple-600 border border-purple-100/80 dark:bg-purple-950/50 dark:text-purple-300 dark:border-purple-900/50'],
        ['icon' => 'zap', 'style' => 'bg-emerald-50 text-emerald-600 border border-emerald-100/80 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-900/50'],
        ['icon' => 'folder', 'style' => 'bg-amber-50 text-amber-600 border border-amber-100/80 dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-900/50'],
        ['icon' => 'flask', 'style' => 'bg-rose-50 text-rose-600 border border-rose-100/80 dark:bg-rose-950/50 dark:text-rose-300 dark:border-rose-900/50'],
    ];

    // Description subtitle snippet for hero card
    $descriptionSummary = 'Keep the goal, requirements, and next steps together in one focused workspace.';
    if ($task->description && $task->description !== 'Created via Nova in WorkMind.') {
        $cleanDesc = trim(preg_replace('/\s+/', ' ', $task->description));
        if (!empty($cleanDesc)) {
            $descriptionSummary = \Illuminate\Support\Str::limit($cleanDesc, 140);
        }
    }
@endphp

@section('content')
<div class="w-full space-y-[14px]" id="task-details-view" data-task-id="{{ $task->id }}" data-task-status="{{ $task->status }}">

    {{-- =========================================================
         HERO HEADER CARD WITH GENERIC PRODUCTIVITY ILLUSTRATION
    ========================================================== --}}
    <section class="task-detail-card task-detail-hero task-detail-reveal relative overflow-hidden rounded-[1.75rem] border border-slate-200/80 bg-white p-5 shadow-[0_8px_30px_-22px_rgba(15,23,42,0.35)] sm:p-7 lg:p-8 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none" style="--task-card-index: 0">
        {{-- Background subtle ambient aura --}}
        <div id="hero-aura-glow" class="pointer-events-none absolute -right-20 -top-20 h-80 w-80 rounded-full bg-gradient-to-br {{ $status['glow'] }} blur-3xl transition-all duration-700"></div>

        <div class="relative flex flex-col justify-between gap-7 md:flex-row md:items-center lg:gap-10">
            {{-- Left: Badges, Title, Summary & Status Switcher --}}
            <div class="min-w-0 flex-1 space-y-4 sm:space-y-5">
                {{-- Badges Row --}}
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
                        <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-bold tracking-wide {{ $catStyle }}">
                            <x-icon name="layers" class="h-3 w-3" />
                            <span>{{ $task->category }}</span>
                        </span>
                    @endif

                    {{-- Overdue Badge if applicable --}}
                    @if($task->is_overdue)
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-rose-200 bg-rose-50 px-2.5 py-0.5 text-xs font-bold text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/60 dark:text-rose-300">
                            <span class="h-1.5 w-1.5 rounded-full bg-rose-500 animate-ping"></span>
                            <span>Overdue</span>
                        </span>
                    @endif
                </div>

                {{-- Task Title --}}
                <h1 id="hero-task-title"
                    class="break-words text-2xl font-black tracking-tight text-slate-950 sm:text-3xl lg:text-4xl dark:text-white transition-colors duration-200 {{ $task->status === 'completed' ? 'line-through text-slate-400 dark:text-slate-500' : '' }}">
                    {{ $task->title }}
                </h1>

                {{-- Subtitle / Summary snippet --}}
                <p id="hero-task-summary" class="max-w-3xl text-sm leading-6 text-slate-500 sm:text-[15px] dark:text-slate-400">
                    {{ $descriptionSummary }}
                </p>

                {{-- 1-Click Segmented Status Pill Container --}}
                <div class="pt-2">
                    <p class="mb-2 text-xs font-bold text-slate-800 dark:text-slate-200">Task Status</p>
                    <div class="inline-flex max-w-full flex-wrap items-center rounded-full border border-slate-200/80 bg-slate-100/80 p-1 dark:border-slate-800 dark:bg-slate-800/80" role="group" aria-label="Task Status Selector">
                        {{-- Pending Option --}}
                        <button type="button"
                            data-set-status="pending"
                            class="status-segment-btn flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-bold transition-all duration-200 {{ $task->status === 'pending' ? 'bg-white text-slate-900 shadow-xs dark:bg-slate-900 dark:text-white border border-slate-200/80 dark:border-slate-700' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' }}">
                            <span class="h-2 w-2 rounded-full bg-slate-400 dark:bg-slate-500"></span>
                            <span>Pending</span>
                        </button>

                        {{-- In Progress Option --}}
                        <button type="button"
                            data-set-status="in_progress"
                            class="status-segment-btn flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-bold transition-all duration-200 {{ $task->status === 'in_progress' ? 'bg-white text-blue-600 shadow-xs dark:bg-slate-900 dark:text-blue-400 border border-slate-200/80 dark:border-slate-700' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' }}">
                            <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                            <span>In Progress</span>
                        </button>

                        {{-- Completed Option --}}
                        <button type="button"
                            data-set-status="completed"
                            class="status-segment-btn flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-bold transition-all duration-200 {{ $task->status === 'completed' ? 'bg-white text-emerald-600 shadow-xs dark:bg-slate-900 dark:text-emerald-400 border border-slate-200/80 dark:border-slate-700' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' }}">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            <span>Completed</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Right: Generic task productivity illustration --}}
            <div class="task-detail-visual relative hidden shrink-0 items-center justify-center p-2 sm:flex lg:p-4" aria-hidden="true">
                {{-- Ambient glowing blur --}}
                <div class="absolute inset-0 rounded-full bg-blue-400/20 blur-2xl"></div>

                {{-- Layered 3D isometric task card --}}
                <div class="task-detail-visual-stage relative h-44 w-52 lg:h-48 lg:w-60">
                    <div class="task-detail-depth-layer task-detail-depth-layer--back absolute inset-0 rounded-2xl" aria-hidden="true"></div>
                    <div class="task-detail-depth-layer task-detail-depth-layer--middle absolute inset-0 rounded-2xl" aria-hidden="true"></div>

                    <div class="task-detail-visual-card absolute inset-0 rounded-2xl border border-white/70 bg-gradient-to-br from-white/95 via-blue-50/80 to-indigo-100/70 p-4 shadow-[0_24px_55px_-18px_rgba(37,99,235,0.42)] backdrop-blur-md dark:border-slate-700/70 dark:from-slate-800/95 dark:via-slate-850 dark:to-slate-900">
                    {{-- Mini browser header dots --}}
                    <div class="task-visual-header flex items-center gap-1.5 border-b border-slate-100/80 pb-2.5 dark:border-slate-700/60">
                        <span class="h-2 w-2 rounded-full bg-blue-400/60"></span>
                        <span class="h-2 w-2 rounded-full bg-slate-300/60 dark:bg-slate-600"></span>
                        <span class="h-2 w-2 rounded-full bg-slate-200/60 dark:bg-slate-700"></span>
                    </div>

                    {{-- Generic checklist content --}}
                    <div class="task-visual-content mt-4 space-y-3">
                        <div class="flex items-center gap-3">
                            <span class="task-visual-check flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white shadow-lg shadow-blue-500/25">
                                <x-icon name="task-logo" class="h-5 w-5" />
                            </span>
                            <div class="min-w-0 flex-1 space-y-1.5">
                                <span class="block h-2 w-4/5 rounded-full bg-blue-200/90 dark:bg-blue-800/70"></span>
                                <span class="block h-1.5 w-3/5 rounded-full bg-slate-200/90 dark:bg-slate-700"></span>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="task-visual-check task-visual-check--second flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white">
                                <x-icon name="check" class="h-3 w-3 stroke-[3]" />
                            </span>
                            <span class="block h-2 flex-1 rounded-full bg-slate-200/80 dark:bg-slate-700"></span>
                        </div>
                        <div class="h-1.5 overflow-hidden rounded-full bg-slate-200/70 dark:bg-slate-700/80">
                            <span class="task-visual-progress block h-full rounded-full bg-gradient-to-r from-blue-500 to-indigo-500"></span>
                        </div>
                    </div>

                    {{-- Floating priority badge (Top Right) --}}
                    <div class="task-visual-float-badge absolute -right-3 -top-3 flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-purple-500 to-indigo-600 text-white shadow-lg shadow-purple-500/30 ring-2 ring-white dark:ring-slate-900">
                        <x-icon name="sparkles" class="h-4 w-4" />
                    </div>

                    {{-- Floating Success Badge (Bottom Right) --}}
                    <div class="task-visual-success absolute -bottom-2 -right-2 flex h-8 w-8 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-400 to-teal-500 text-white shadow-lg shadow-emerald-500/30 ring-2 ring-white dark:ring-slate-900">
                        <x-icon name="check" class="h-4 w-4 stroke-[3]" />
                    </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


    {{-- =========================================================
         3. MAIN 2-COLUMN PRODUCTIVITY GRID
    ========================================================== --}}
    <div class="grid grid-cols-1 gap-[14px] lg:grid-cols-12">

        {{-- =====================================================
             LEFT COLUMN (8 cols): DESCRIPTION & CHECKLIST
        ====================================================== --}}
        <div class="space-y-[14px] lg:col-span-8">

            {{-- 1. DESCRIPTION & TAGS CARD --}}
            <section class="task-detail-card task-detail-reveal rounded-[1.75rem] border border-slate-200/80 bg-white p-5 shadow-[0_8px_30px_-22px_rgba(15,23,42,0.35)] sm:p-6 lg:p-7 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none" style="--task-card-index: 2">
                {{-- Description Header --}}
                <div class="flex items-center justify-between pb-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400">
                            <x-icon name="clipboard" class="h-4.5 w-4.5" />
                        </span>
                        <h2 class="text-base font-bold text-slate-950 dark:text-white">Description</h2>
                    </div>

                    <button type="button"
                        id="btn-toggle-edit-desc"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-600 hover:text-blue-700 hover:underline dark:text-blue-400">
                        <x-icon name="edit" class="h-3.5 w-3.5" />
                        <span id="text-edit-desc-action">Edit description</span>
                    </button>
                </div>

                {{-- Description Display View --}}
                <div id="desc-view-container" class="mt-2">
                    <div class="min-h-24 rounded-2xl border border-slate-100 bg-slate-50/70 p-4 text-sm leading-6 text-slate-700 sm:min-h-28 sm:p-5 dark:border-slate-800/80 dark:bg-slate-800/50 dark:text-slate-300">
                        <p id="desc-text-content" class="whitespace-pre-wrap break-words">
                            {{ $task->description ?: 'Created via Nova in WorkMind.' }}
                        </p>
                    </div>
                </div>

                {{-- Compact Inline Edit Form (Hidden by default) --}}
                <form id="form-edit-description" class="mt-3 hidden space-y-3">
                    <textarea id="input-edit-description"
                        rows="5"
                        class="min-h-36 w-full resize-y rounded-2xl border border-slate-200 bg-white p-4 text-sm leading-6 text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:placeholder:text-slate-500 dark:focus:ring-blue-900/40"
                        placeholder="Write task details or requirements...">{{ $task->description }}</textarea>
                    <div class="flex items-center justify-end gap-2">
                        <button type="button" id="btn-cancel-desc" class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                            Cancel
                        </button>
                        <button type="submit" class="rounded-xl bg-blue-600 px-4 py-1.5 text-xs font-bold text-white shadow-xs hover:bg-blue-700">
                            Save Changes
                        </button>
                    </div>
                </form>

                {{-- Tags Section --}}
                <div class="mt-6 pt-5 border-t border-slate-100 dark:border-slate-800">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <x-icon name="tag" class="h-3.5 w-3.5 text-slate-500" />
                            <span class="text-xs font-bold text-slate-900 dark:text-white">Tags</span>
                        </div>

                        {{-- Small '+' button to add new tag inline --}}
                        <button type="button"
                            id="btn-add-tag-trigger"
                            class="inline-flex h-6 w-6 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:border-blue-300 hover:text-blue-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 transition"
                            title="Add tag">
                            <x-icon name="plus" class="h-3.5 w-3.5" />
                        </button>
                    </div>

                    {{-- Inline compact Add Tag input form --}}
                    <form id="form-inline-add-tag" class="mb-3 hidden flex items-center gap-2">
                        <input type="text"
                            id="input-inline-new-tag"
                            placeholder="Type tag name..."
                            class="h-8 rounded-lg border border-slate-200 bg-white px-3 text-xs text-slate-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                        <button type="submit" class="h-8 rounded-lg bg-blue-600 px-3 text-xs font-bold text-white hover:bg-blue-700">
                            Add
                        </button>
                        <button type="button" id="btn-cancel-inline-tag" class="h-8 rounded-lg border border-slate-200 px-2 text-xs text-slate-500 hover:bg-slate-50 dark:border-slate-700">
                            ✕
                        </button>
                    </form>

                    {{-- Tags Container --}}
                    <div class="flex flex-wrap gap-2" id="detail-tags-container" aria-label="Tags">
                        @if($task->tags && count($task->tags) > 0)
                            @foreach($task->tags as $tag)
                                <span class="group inline-flex items-center gap-1 rounded-xl border border-blue-100 bg-blue-50/70 px-3 py-1 text-xs font-semibold text-blue-700 transition hover:bg-blue-100/70 dark:border-blue-900/40 dark:bg-blue-950/40 dark:text-blue-300">
                                    <span class="text-blue-500 dark:text-blue-400">#</span>
                                    <span>{{ $tag }}</span>
                                </span>
                            @endforeach
                        @else
                            <span class="text-xs text-slate-400 dark:text-slate-500" id="tags-empty-hint">No tags attached. Click + to add.</span>
                        @endif
                    </div>
                </div>
            </section>


            {{-- 2. CHECKLIST & SUBTASKS CARD --}}
            <section class="task-detail-card task-detail-reveal rounded-[1.75rem] border border-slate-200/80 bg-white p-5 shadow-[0_8px_30px_-22px_rgba(15,23,42,0.35)] sm:p-6 lg:p-7 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none" id="checklist-card" style="--task-card-index: 3">
                {{-- Header with Pill Counter & AI Breakdown Button --}}
                <div class="flex flex-wrap items-center justify-between gap-3 pb-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                            <x-icon name="check-circle" class="h-4.5 w-4.5" />
                        </span>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-base font-bold text-slate-950 dark:text-white">Checklist</h2>
                                <span id="checklist-counter-badge" class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                    <span id="detail-completed-count">{{ $task->completed_subtasks_count }}</span>/<span id="detail-total-count">{{ $task->subtasks_count }}</span>
                                </span>
                            </div>
                            <p class="text-xs text-slate-400 dark:text-slate-500">Break work down into manageable, actionable milestones.</p>
                        </div>
                    </div>

                    {{-- AI Magic Breakdown Pill Button --}}
                    <button type="button"
                        id="btn-detail-ai-breakdown"
                        data-ai-detail-breakdown="{{ $task->id }}"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-purple-200/80 bg-purple-50/80 px-3.5 py-1.5 text-xs font-bold text-purple-700 shadow-2xs transition hover:border-purple-300 hover:bg-purple-100 dark:border-purple-900/60 dark:bg-purple-950/50 dark:text-purple-300 dark:hover:bg-purple-900/60"
                        title="Generate smart subtasks using Nova AI">
                        <x-icon name="sparkles" class="h-3.5 w-3.5 text-purple-600 dark:text-purple-400" />
                        <span>AI Breakdown</span>
                    </button>
                </div>

                {{-- Thin Sleek Progress Bar --}}
                <div class="mt-2 flex items-center gap-3" id="checklist-progress-container">
                    <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                        <div id="detail-progress-bar"
                            class="h-full rounded-full bg-emerald-500 transition-all duration-500"
                            style="width: {{ $progressPercent }}%"></div>
                    </div>
                    <span id="detail-progress-percent" class="text-xs font-extrabold text-emerald-600 dark:text-emerald-400">{{ $progressPercent }}%</span>
                </div>

                {{-- Subtasks Cards List --}}
                <div class="mt-5 space-y-2.5" id="detail-subtasks-list">
                    @forelse($task->subtasks ?? [] as $index => $subtask)
                        @php
                            $stId = $subtask['id'] ?? (string) \Illuminate\Support\Str::uuid();
                            $isDone = !empty($subtask['completed']);
                            $iconData = $subtaskIconPalette[$index % count($subtaskIconPalette)];
                        @endphp
                        <div class="group flex items-center justify-between gap-3 rounded-2xl border border-slate-100 bg-white p-3.5 shadow-2xs transition hover:border-slate-200 hover:shadow-xs dark:border-slate-800/80 dark:bg-slate-900 dark:hover:border-slate-700"
                            data-subtask-item-id="{{ $stId }}">
                            <label class="flex min-w-0 flex-1 cursor-pointer items-center gap-3">
                                {{-- Checkbox --}}
                                <input type="checkbox"
                                    data-task-subtask-toggle="{{ $task->id }}"
                                    data-subtask-id="{{ $stId }}"
                                    {{ $isDone ? 'checked' : '' }}
                                    class="h-5 w-5 rounded-lg border-slate-300 text-blue-600 focus:ring-0 dark:border-slate-600 dark:bg-slate-800 transition cursor-pointer">

                                {{-- Category Icon Box --}}
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl {{ $iconData['style'] }}">
                                    <x-icon :name="$iconData['icon']" class="h-4 w-4" />
                                </span>

                                {{-- Title Text --}}
                                <span class="subtask-title-text break-words text-xs sm:text-sm font-medium transition-colors duration-200 {{ $isDone ? 'line-through text-slate-400 dark:text-slate-500' : 'text-slate-800 dark:text-slate-200' }}">
                                    {{ $subtask['title'] }}
                                </span>
                            </label>

                            <div class="flex items-center gap-1">
                                {{-- Delete Button on Hover --}}
                                <button type="button"
                                    data-delete-subtask-id="{{ $stId }}"
                                    class="opacity-0 group-hover:opacity-100 rounded-lg p-1 text-slate-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40 dark:hover:text-rose-400 transition"
                                    title="Delete milestone">
                                    <x-icon name="trash" class="h-3.5 w-3.5" />
                                </button>

                                {{-- Drag Handle Dots --}}
                                <span class="text-slate-300 dark:text-slate-600 cursor-grab text-xs font-mono select-none px-1">
                                    :::
                                </span>
                            </div>
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

                {{-- Compact Inline Add New Subtask Form --}}
                <form id="form-add-subtask" class="mt-5 flex items-center gap-2">
                    @csrf
                    <input type="text"
                        id="input-new-subtask"
                        placeholder="Add a new checklist step... (Press Enter)"
                        class="h-10 flex-1 rounded-xl border border-slate-200/90 bg-slate-50/60 px-4 text-xs sm:text-sm text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-200 dark:placeholder:text-slate-500"
                        required>
                    <button type="submit"
                        class="inline-flex h-10 items-center gap-1.5 rounded-xl bg-blue-600 px-4 text-xs font-bold text-white shadow-xs transition hover:bg-blue-700 active:scale-95">
                        <x-icon name="plus" class="h-3.5 w-3.5" />
                        <span>Add Step</span>
                    </button>
                </form>
            </section>
        </div>


        {{-- =====================================================
             RIGHT COLUMN (4 cols): PROPERTIES & TIMELINE
        ====================================================== --}}
        <div class="space-y-[14px] lg:col-span-4">

            {{-- 1. PROPERTIES CARD --}}
            <section class="task-detail-card task-detail-reveal rounded-[1.75rem] border border-slate-200/80 bg-white p-5 shadow-[0_8px_30px_-22px_rgba(15,23,42,0.35)] sm:p-6 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none" style="--task-card-index: 2">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2">
                        <x-icon name="settings" class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 dark:text-white">Properties</h3>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-slate-400 font-mono">#{{ $task->id }}</span>
                        <a href="{{ route('tasks.edit', $task) }}"
                            data-edit-task-id="{{ $task->id }}"
                            class="inline-flex h-7 w-7 items-center justify-center rounded-lg text-slate-400 transition hover:bg-blue-50 hover:text-blue-600 dark:hover:bg-blue-950/50 dark:hover:text-blue-400"
                            aria-label="Edit task" title="Edit task">
                            <x-icon name="edit" class="h-3.5 w-3.5" />
                        </a>
                    </div>
                </div>

                <div class="divide-y divide-slate-100 text-xs dark:divide-slate-800">
                    {{-- Status Row with Dropdown --}}
                    <div class="flex items-center justify-between py-2.5">
                        <span class="flex items-center gap-2 text-slate-500 dark:text-slate-400">
                            <x-icon name="circle" class="h-3.5 w-3.5" />
                            <span>Status</span>
                        </span>
                        <div class="relative">
                            <select id="select-prop-status"
                                class="cursor-pointer appearance-none rounded-full border border-blue-200/80 bg-blue-50/70 py-1 pl-3 pr-7 text-xs font-bold text-blue-700 shadow-2xs outline-none transition hover:bg-blue-100/70 dark:border-blue-900/60 dark:bg-blue-950/40 dark:text-blue-300">
                                <option value="pending" {{ $task->status === 'pending' ? 'selected' : '' }}>● Pending</option>
                                <option value="in_progress" {{ $task->status === 'in_progress' ? 'selected' : '' }}>● In Progress</option>
                                <option value="completed" {{ $task->status === 'completed' ? 'selected' : '' }}>● Completed</option>
                            </select>
                            <x-icon name="chevron-down" class="pointer-events-none absolute right-2.5 top-1/2 h-3 w-3 -translate-y-1/2 text-blue-500 dark:text-blue-400" />
                        </div>
                    </div>

                    {{-- Priority Row with Dropdown --}}
                    <div class="flex items-center justify-between py-2.5">
                        <span class="flex items-center gap-2 text-slate-500 dark:text-slate-400">
                            <x-icon name="flag" class="h-3.5 w-3.5" />
                            <span>Priority</span>
                        </span>
                        <div class="relative">
                            <select id="select-prop-priority"
                                class="cursor-pointer appearance-none rounded-full border border-amber-200/80 bg-amber-50/70 py-1 pl-3 pr-7 text-xs font-bold text-amber-700 shadow-2xs outline-none transition hover:bg-amber-100/70 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-300">
                                <option value="low" {{ $task->priority === 'low' ? 'selected' : '' }}>⚑ Low Priority</option>
                                <option value="medium" {{ $task->priority === 'medium' ? 'selected' : '' }}>⚑ Medium Priority</option>
                                <option value="high" {{ $task->priority === 'high' ? 'selected' : '' }}>⚑ High Priority</option>
                            </select>
                            <x-icon name="chevron-down" class="pointer-events-none absolute right-2.5 top-1/2 h-3 w-3 -translate-y-1/2 text-amber-600 dark:text-amber-400" />
                        </div>
                    </div>

                    {{-- Category Row --}}
                    <div class="flex items-center justify-between py-2.5">
                        <span class="flex items-center gap-2 text-slate-500 dark:text-slate-400">
                            <x-icon name="layers" class="h-3.5 w-3.5" />
                            <span>Category</span>
                        </span>
                        <span class="rounded-lg border px-2.5 py-0.5 font-bold {{ $catStyle }}">
                            {{ $task->category ?: 'Work' }}
                        </span>
                    </div>

                    {{-- Due Date Row --}}
                    <div class="flex items-center justify-between py-2.5">
                        <span class="flex items-center gap-2 text-slate-500 dark:text-slate-400">
                            <x-icon name="calendar" class="h-3.5 w-3.5" />
                            <span>Due Date</span>
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200/80 bg-white px-3 py-0.5 text-xs font-semibold text-slate-700 shadow-2xs dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                            <span class="h-1.5 w-1.5 rounded-full {{ $task->due_date ? 'bg-blue-500' : 'bg-slate-400' }}"></span>
                            <span>{{ $task->due_date ? $task->due_date->format('M j, Y') : 'No due date' }}</span>
                            <x-icon name="chevron-down" class="h-2.5 w-2.5 text-slate-400" />
                        </span>
                    </div>

                    {{-- End Date Row --}}
                    <div class="flex items-center justify-between py-2.5">
                        <span class="flex items-center gap-2 text-slate-500 dark:text-slate-400">
                            <x-icon name="clock" class="h-3.5 w-3.5" />
                            <span>End Date</span>
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200/80 bg-white px-3 py-0.5 text-xs font-semibold text-slate-700 shadow-2xs dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                            <span class="h-1.5 w-1.5 rounded-full {{ $task->end_date ? 'bg-purple-500' : 'bg-slate-400' }}"></span>
                            <span>{{ $task->end_date ? $task->end_date->format('M j, Y') : 'No end date' }}</span>
                            <x-icon name="chevron-down" class="h-2.5 w-2.5 text-slate-400" />
                        </span>
                    </div>

                    {{-- Updated Row --}}
                    <div class="flex items-center justify-between py-2.5">
                        <span class="flex items-center gap-2 text-slate-500 dark:text-slate-400">
                            <x-icon name="activity" class="h-3.5 w-3.5" />
                            <span>Updated</span>
                        </span>
                        <span id="prop-updated-text" class="text-slate-700 dark:text-slate-300 font-medium">
                            {{ $task->updated_at->diffForHumans() }}
                        </span>
                    </div>

                    {{-- Created Row --}}
                    <div class="flex items-center justify-between py-2.5">
                        <span class="flex items-center gap-2 text-slate-500 dark:text-slate-400">
                            <x-icon name="calendar" class="h-3.5 w-3.5" />
                            <span>Created</span>
                        </span>
                        <span class="text-slate-700 dark:text-slate-300 font-medium">
                            {{ $task->created_at->format('M j, Y') }}
                        </span>
                    </div>
                </div>
            </section>


            {{-- 2. TIMELINE CARD --}}
            <section class="task-detail-card task-detail-reveal rounded-[1.75rem] border border-slate-200/80 bg-white p-5 shadow-[0_8px_30px_-22px_rgba(15,23,42,0.35)] sm:p-6 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none" style="--task-card-index: 3">
                <div class="flex items-center gap-2 pb-3">
                    <x-icon name="clock" class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 dark:text-white">Timeline</h3>
                </div>

                <div class="relative pl-5 space-y-4 before:absolute before:left-1.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200 dark:before:bg-slate-800">
                    {{-- 1. Created --}}
                    <div class="relative">
                        <span class="absolute -left-5 top-1 h-3 w-3 rounded-full border-2 border-white bg-blue-600 dark:border-slate-900"></span>
                        <p class="text-xs font-bold text-slate-900 dark:text-white">Created</p>
                        <p class="text-[11px] text-slate-400">{{ $task->created_at->format('M j, Y · g:i A') }}</p>
                    </div>

                    {{-- 2. Last Activity --}}
                    <div class="relative">
                        <span class="absolute -left-5 top-1 h-3 w-3 rounded-full border-2 border-white bg-purple-600 dark:border-slate-900"></span>
                        <p class="text-xs font-bold text-slate-900 dark:text-white">Last Activity</p>
                        <p class="text-[11px] text-slate-400">{{ $task->updated_at->diffForHumans() }}</p>
                    </div>

                    {{-- 3. Deadline --}}
                    <div class="relative">
                        <span class="absolute -left-5 top-1 h-3 w-3 rounded-full border-2 border-white {{ $task->is_overdue ? 'bg-rose-500' : 'bg-emerald-500' }} dark:border-slate-900"></span>
                        <p class="text-xs font-bold text-slate-900 dark:text-white">Deadline</p>
                        <p class="text-[11px] text-slate-400">
                            {{ $task->due_date ? $task->due_date->format('M j, Y') : 'No deadline set' }}
                        </p>
                    </div>
                </div>
            </section>
        </div>

    </div>
</div>

{{-- Inline JavaScript for Interactive Features --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
    const taskId = '{{ $task->id }}';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // =========================================================
    // 1. ESCAPE KEY TO GO BACK
    // =========================================================
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !document.querySelector('.swal2-container') && !document.querySelector('#task-modal:not(.hidden)')) {
            window.location.assign('{{ route("tasks.index") }}');
        }
    });

    // =========================================================
    // 2. LIVE STATUS SWITCHER
    // =========================================================
    const statusBtns = document.querySelectorAll('[data-set-status]');
    const heroStatusBadge = document.getElementById('hero-status-badge');
    const heroStatusDot = document.getElementById('hero-status-dot');
    const heroStatusText = document.getElementById('hero-status-text');
    const heroTaskTitle = document.getElementById('hero-task-title');
    const selectPropStatus = document.getElementById('select-prop-status');
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

                // Segmented button updates
                statusBtns.forEach(btn => {
                    const active = btn.dataset.setStatus === data.status;
                    if (active) {
                        btn.className = `status-segment-btn flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-bold transition-all duration-200 bg-white dark:bg-slate-900 shadow-xs border border-slate-200/80 dark:border-slate-700 ${
                            data.status === 'completed' ? 'text-emerald-600 dark:text-emerald-400' : (data.status === 'in_progress' ? 'text-blue-600 dark:text-blue-400' : 'text-slate-900 dark:text-white')
                        }`;
                    } else {
                        btn.className = 'status-segment-btn flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-bold transition-all duration-200 text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white';
                    }
                });

                // Status badge updates
                if (data.status === 'completed') {
                    heroStatusBadge.className = 'inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-bold tracking-wide bg-emerald-50 text-emerald-700 border-emerald-200/80 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-900/60';
                    heroStatusDot.className = 'h-2 w-2 rounded-full bg-emerald-500 animate-pulse';
                    heroStatusText.textContent = 'Completed';
                    heroTaskTitle.classList.add('line-through', 'text-slate-400', 'dark:text-slate-500');
                    if (heroAuraGlow) heroAuraGlow.className = 'pointer-events-none absolute -right-20 -top-20 h-80 w-80 rounded-full bg-gradient-to-br from-emerald-400/10 via-transparent to-transparent blur-3xl transition-all duration-700';

                    if (typeof playTaskChime === 'function') playTaskChime();
                    if (typeof fireConfetti === 'function') fireConfetti();
                    if (typeof showToast === 'function') showToast('success', 'Task marked as completed! 🎉');
                } else if (data.status === 'in_progress') {
                    heroStatusBadge.className = 'inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-bold tracking-wide bg-blue-50 text-blue-700 border-blue-200/80 dark:bg-blue-950/50 dark:text-blue-300 dark:border-blue-900/60';
                    heroStatusDot.className = 'h-2 w-2 rounded-full bg-blue-500 animate-pulse';
                    heroStatusText.textContent = 'In Progress';
                    heroTaskTitle.classList.remove('line-through', 'text-slate-400', 'dark:text-slate-500');
                    if (heroAuraGlow) heroAuraGlow.className = 'pointer-events-none absolute -right-20 -top-20 h-80 w-80 rounded-full bg-gradient-to-br from-blue-400/10 via-transparent to-transparent blur-3xl transition-all duration-700';
                    if (typeof showToast === 'function') showToast('info', 'Task set to In Progress');
                } else {
                    heroStatusBadge.className = 'inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-bold tracking-wide bg-amber-50 text-amber-700 border-amber-200/80 dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-900/60';
                    heroStatusDot.className = 'h-2 w-2 rounded-full bg-amber-500 animate-pulse';
                    heroStatusText.textContent = 'Pending';
                    heroTaskTitle.classList.remove('line-through', 'text-slate-400', 'dark:text-slate-500');
                    if (heroAuraGlow) heroAuraGlow.className = 'pointer-events-none absolute -right-20 -top-20 h-80 w-80 rounded-full bg-gradient-to-br from-amber-400/10 via-transparent to-transparent blur-3xl transition-all duration-700';
                    if (typeof showToast === 'function') showToast('info', 'Task reopened to Pending');
                }

                if (selectPropStatus) selectPropStatus.value = data.status;
            }
        } catch (_err) {
            if (typeof showToast === 'function') showToast('error', 'Failed to update status');
        }
    }

    statusBtns.forEach(btn => {
        btn.addEventListener('click', () => applyStatusUpdate(btn.dataset.setStatus));
    });

    selectPropStatus?.addEventListener('change', (e) => {
        applyStatusUpdate(e.target.value);
    });

    // =========================================================
    // 5. PRIORITY DROPDOWN QUICK UPDATE
    // =========================================================
    const selectPropPriority = document.getElementById('select-prop-priority');
    selectPropPriority?.addEventListener('change', async (e) => {
        try {
            const res = await fetch(`/tasks/${taskId}/quick-update`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ priority: e.target.value }),
            });
            const data = await res.json();
            if (data.success && typeof showToast === 'function') {
                showToast('success', 'Priority updated');
            }
        } catch (_err) {}
    });

    // =========================================================
    // 6. INLINE EDIT DESCRIPTION FORM
    // =========================================================
    const btnToggleEditDesc = document.getElementById('btn-toggle-edit-desc');
    const descViewContainer = document.getElementById('desc-view-container');
    const formEditDesc = document.getElementById('form-edit-description');
    const inputEditDesc = document.getElementById('input-edit-description');
    const descTextContent = document.getElementById('desc-text-content');
    const heroTaskSummary = document.getElementById('hero-task-summary');
    const btnCancelDesc = document.getElementById('btn-cancel-desc');

    btnToggleEditDesc?.addEventListener('click', () => {
        descViewContainer.classList.toggle('hidden');
        formEditDesc.classList.toggle('hidden');
        if (!formEditDesc.classList.contains('hidden')) {
            inputEditDesc.focus();
        }
    });

    btnCancelDesc?.addEventListener('click', () => {
        descViewContainer.classList.remove('hidden');
        formEditDesc.classList.add('hidden');
    });

    formEditDesc?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const newDesc = inputEditDesc.value.trim();

        try {
            const res = await fetch(`/tasks/${taskId}/quick-update`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ description: newDesc }),
            });
            const data = await res.json();
            if (data.success) {
                descTextContent.textContent = newDesc || 'Created via Nova in WorkMind.';
                if (heroTaskSummary && newDesc) {
                    heroTaskSummary.textContent = newDesc.length > 140 ? newDesc.substring(0, 140) + '...' : newDesc;
                }
                descViewContainer.classList.remove('hidden');
                formEditDesc.classList.add('hidden');
                if (typeof showToast === 'function') showToast('success', 'Description updated');
            }
        } catch (_err) {
            if (typeof showToast === 'function') showToast('error', 'Failed to update description');
        }
    });

    // =========================================================
    // 7. INLINE QUICK ADD TAG FORM
    // =========================================================
    const btnAddTagTrigger = document.getElementById('btn-add-tag-trigger');
    const formInlineAddTag = document.getElementById('form-inline-add-tag');
    const inputInlineNewTag = document.getElementById('input-inline-new-tag');
    const btnCancelInlineTag = document.getElementById('btn-cancel-inline-tag');
    const tagsContainer = document.getElementById('detail-tags-container');

    btnAddTagTrigger?.addEventListener('click', () => {
        formInlineAddTag.classList.remove('hidden');
        inputInlineNewTag.focus();
    });

    btnCancelInlineTag?.addEventListener('click', () => {
        formInlineAddTag.classList.add('hidden');
        inputInlineNewTag.value = '';
    });

    formInlineAddTag?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const tagVal = inputInlineNewTag.value.trim().replace(/^#/, '');
        if (!tagVal) return;

        // Collect existing tags
        const existingTags = [];
        tagsContainer.querySelectorAll('span.group span:last-child').forEach(s => {
            existingTags.push(s.textContent.trim());
        });
        if (!existingTags.includes(tagVal)) {
            existingTags.push(tagVal);
        }

        try {
            const res = await fetch(`/tasks/${taskId}/quick-update`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ tags: existingTags }),
            });
            const data = await res.json();
            if (data.success) {
                document.getElementById('tags-empty-hint')?.remove();
                const newTagEl = document.createElement('span');
                newTagEl.className = 'group inline-flex items-center gap-1 rounded-xl border border-blue-100 bg-blue-50/70 px-3 py-1 text-xs font-semibold text-blue-700 transition hover:bg-blue-100/70 dark:border-blue-900/40 dark:bg-blue-950/40 dark:text-blue-300';
                newTagEl.innerHTML = `<span class="text-blue-500 dark:text-blue-400">#</span><span>${escapeHtml(tagVal)}</span>`;
                tagsContainer.appendChild(newTagEl);
                inputInlineNewTag.value = '';
                formInlineAddTag.classList.add('hidden');
                if (typeof showToast === 'function') showToast('success', 'Tag added');
            }
        } catch (_err) {}
    });

    // =========================================================
    // 8. PIN / UNPIN TOGGLE
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
                    document.querySelectorAll('.detail-pin-text').forEach(t => t.textContent = isPinned ? 'Unpin' : 'Pin');
                    if (typeof showToast === 'function') showToast('success', isPinned ? 'Task pinned to top' : 'Task unpinned');
                }
            } catch (_err) {}
        });
    });

    // =========================================================
    // 9. CHECKLIST PROGRESS RE-CALCULATOR
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
    // 10. CHECKLIST SUBTASKS INTERACTION
    // =========================================================
    const subtasksList = document.getElementById('detail-subtasks-list');
    const iconPalette = [
        { icon: 'book', style: 'bg-blue-50 text-blue-600 border border-blue-100/80 dark:bg-blue-950/50 dark:text-blue-300 dark:border-blue-900/50' },
        { icon: 'layers', style: 'bg-purple-50 text-purple-600 border border-purple-100/80 dark:bg-purple-950/50 dark:text-purple-300 dark:border-purple-900/50' },
        { icon: 'zap', style: 'bg-emerald-50 text-emerald-600 border border-emerald-100/80 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-900/50' },
        { icon: 'folder', style: 'bg-amber-50 text-amber-600 border border-amber-100/80 dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-900/50' },
        { icon: 'flask', style: 'bg-rose-50 text-rose-600 border border-rose-100/80 dark:bg-rose-950/50 dark:text-rose-300 dark:border-rose-900/50' }
    ];

    function getIconSvg(iconName) {
        switch(iconName) {
            case 'book': return '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10"/><path d="M6 10h10"/></svg>';
            case 'layers': return '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m12 2 9 5-9 5-9-5Z"/><path d="m3 12 9 5 9-5"/><path d="m3 17 9 5 9-5"/></svg>';
            case 'zap': return '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>';
            case 'folder': return '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/></svg>';
            case 'flask': return '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 2v7.527a2 2 0 0 1-.211.896L4.72 20.55a1 1 0 0 0 .9 1.45h12.76a1 1 0 0 0 .9-1.45l-5.069-10.127A2 2 0 0 1 14 9.527V2"/><path d="M8.5 2h7"/><path d="M7 16h10"/></svg>';
            default: return '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/></svg>';
        }
    }

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
    // 11. COMPACT INLINE ADD SUBTASK FORM
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

                const currentCount = subtasksList.querySelectorAll('[data-subtask-item-id]').length;
                const iconInfo = iconPalette[currentCount % iconPalette.length];

                const item = document.createElement('div');
                item.className = 'group flex items-center justify-between gap-3 rounded-2xl border border-slate-100 bg-white p-3.5 shadow-2xs transition hover:border-slate-200 hover:shadow-xs dark:border-slate-800/80 dark:bg-slate-900 dark:hover:border-slate-700';
                item.dataset.subtaskItemId = data.subtask.id;
                item.innerHTML = `
                    <label class="flex min-w-0 flex-1 cursor-pointer items-center gap-3">
                        <input type="checkbox"
                            data-task-subtask-toggle="${taskId}"
                            data-subtask-id="${data.subtask.id}"
                            class="h-5 w-5 rounded-lg border-slate-300 text-blue-600 focus:ring-0 dark:border-slate-600 dark:bg-slate-800 transition cursor-pointer">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl ${iconInfo.style}">
                            ${getIconSvg(iconInfo.icon)}
                        </span>
                        <span class="subtask-title-text break-words text-xs sm:text-sm font-medium transition-colors duration-200 text-slate-800 dark:text-slate-200">
                            ${escapeHtml(data.subtask.title)}
                        </span>
                    </label>
                    <div class="flex items-center gap-1">
                        <button type="button"
                            data-delete-subtask-id="${data.subtask.id}"
                            class="opacity-0 group-hover:opacity-100 rounded-lg p-1 text-slate-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40 dark:hover:text-rose-400 transition"
                            title="Delete milestone">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M10 11v6M14 11v6"/></svg>
                        </button>
                        <span class="text-slate-300 dark:text-slate-600 cursor-grab text-xs font-mono select-none px-1">
                            :::
                        </span>
                    </div>
                `;
                subtasksList.appendChild(item);
                updateChecklistProgress(data.progress, data.completed_count, data.total_count);
            }
        } catch (_err) {}
    });

    // =========================================================
    // 12. AI BREAKDOWN BUTTONS (HEADER & DROPDOWN)
    // =========================================================
    async function triggerAiBreakdown(btn) {
        if (btn.dataset.loading === 'true') return;
        btn.dataset.loading = 'true';
        const originalHtml = btn.innerHTML;
        btn.innerHTML = `
            <svg class="h-3.5 w-3.5 animate-spin text-purple-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10" stroke-opacity="0.25"/>
                <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"/>
            </svg>
            <span class="text-xs font-bold text-purple-700 dark:text-purple-300">Generating...</span>
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

            if (!res.ok) throw new Error('AI breakdown failed');
            const data = await res.json();

            if (data.success && Array.isArray(data.subtasks)) {
                if (typeof playTaskChime === 'function') playTaskChime();
                if (typeof fireConfetti === 'function') fireConfetti();

                document.getElementById('checklist-empty-state')?.remove();
                subtasksList.innerHTML = data.subtasks.map((st, i) => {
                    const iconInfo = iconPalette[i % iconPalette.length];
                    return `
                        <div class="group flex items-center justify-between gap-3 rounded-2xl border border-slate-100 bg-white p-3.5 shadow-2xs transition hover:border-slate-200 hover:shadow-xs dark:border-slate-800/80 dark:bg-slate-900 dark:hover:border-slate-700"
                            data-subtask-item-id="${st.id}">
                            <label class="flex min-w-0 flex-1 cursor-pointer items-center gap-3">
                                <input type="checkbox"
                                    data-task-subtask-toggle="${taskId}"
                                    data-subtask-id="${st.id}"
                                    ${st.completed ? 'checked' : ''}
                                    class="h-5 w-5 rounded-lg border-slate-300 text-blue-600 focus:ring-0 dark:border-slate-600 dark:bg-slate-800 transition cursor-pointer">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl ${iconInfo.style}">
                                    ${getIconSvg(iconInfo.icon)}
                                </span>
                                <span class="subtask-title-text break-words text-xs sm:text-sm font-medium transition-colors duration-200 ${st.completed ? 'line-through text-slate-400 dark:text-slate-500' : 'text-slate-800 dark:text-slate-200'}">
                                    ${escapeHtml(st.title)}
                                </span>
                            </label>
                            <div class="flex items-center gap-1">
                                <button type="button"
                                    data-delete-subtask-id="${st.id}"
                                    class="opacity-0 group-hover:opacity-100 rounded-lg p-1 text-slate-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40 dark:hover:text-rose-400 transition"
                                    title="Delete milestone">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M10 11v6M14 11v6"/></svg>
                                </button>
                                <span class="text-slate-300 dark:text-slate-600 cursor-grab text-xs font-mono select-none px-1">
                                    :::
                                </span>
                            </div>
                        </div>
                    `;
                }).join('');

                updateChecklistProgress(data.subtasks_progress, data.completed_count, data.subtasks_count);
                if (typeof showToast === 'function') showToast('success', 'Nova generated smart checklist steps!');
            }
        } catch (_err) {
            if (typeof showToast === 'function') showToast('error', 'Could not generate AI subtasks');
        } finally {
            btn.dataset.loading = 'false';
            btn.innerHTML = originalHtml;
        }
    }

    const aiBreakdownBtn = document.getElementById('btn-detail-ai-breakdown');
    aiBreakdownBtn?.addEventListener('click', () => triggerAiBreakdown(aiBreakdownBtn));

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
