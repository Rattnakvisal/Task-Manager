@php
    $task = $task ?? null;
    $currentPriority = old('priority', $task->priority ?? 'medium');
    $currentStatus = old('status', $task->status ?? 'pending');
    $currentCategory = old('category', $task->category ?? 'Work');

    // Decode existing or old subtasks
    $initialSubtasks = [];
    if (old('subtasks')) {
        $oldSt = old('subtasks');
        $initialSubtasks = is_array($oldSt) ? $oldSt : (json_decode($oldSt, true) ?: []);
    } elseif ($task && !empty($task->subtasks)) {
        $initialSubtasks = is_array($task->subtasks) ? $task->subtasks : (json_decode($task->subtasks, true) ?: []);
    }

    // Decode existing or old tags
    $initialTags = [];
    if (old('tags')) {
        $oldTags = old('tags');
        $initialTags = is_array($oldTags) ? $oldTags : (json_decode($oldTags, true) ?: array_filter(array_map('trim', explode(',', $oldTags))));
    } elseif ($task && !empty($task->tags)) {
        $initialTags = is_array($task->tags) ? $task->tags : (json_decode($task->tags, true) ?: []);
    }

    $subtaskIconPalette = [
        ['icon' => 'book', 'style' => 'bg-blue-50 text-blue-600 border border-blue-100 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-900/50'],
        ['icon' => 'layers', 'style' => 'bg-purple-50 text-purple-600 border border-purple-100 dark:bg-purple-950/60 dark:text-purple-300 dark:border-purple-900/50'],
        ['icon' => 'zap', 'style' => 'bg-emerald-50 text-emerald-600 border border-emerald-100 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-900/50'],
        ['icon' => 'folder', 'style' => 'bg-amber-50 text-amber-600 border border-amber-100 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-900/50'],
        ['icon' => 'flask', 'style' => 'bg-rose-50 text-rose-600 border border-rose-100 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-900/50'],
    ];

    $categoriesList = [
        ['name' => 'Work', 'icon' => 'briefcase', 'color' => 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/50 dark:text-purple-300'],
        ['name' => 'Personal', 'icon' => 'home', 'color' => 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/50 dark:text-indigo-300'],
        ['name' => 'Dev', 'icon' => 'code', 'color' => 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-950/50 dark:text-sky-300'],
        ['name' => 'Design', 'icon' => 'palette', 'color' => 'bg-pink-50 text-pink-700 border-pink-200 dark:bg-pink-950/50 dark:text-pink-300'],
        ['name' => 'Urgent', 'icon' => 'zap', 'color' => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/50 dark:text-rose-300'],
        ['name' => 'Study', 'icon' => 'book', 'color' => 'bg-teal-50 text-teal-700 border-teal-200 dark:bg-teal-950/50 dark:text-teal-300'],
        ['name' => 'Finance', 'icon' => 'credit-card', 'color' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300'],
    ];
@endphp

<div class="space-y-4" id="clean-task-small-form">

    {{-- Hidden Form Values --}}
    <input type="hidden" name="priority" id="hidden-task-priority" value="{{ $currentPriority }}">
    <input type="hidden" name="status" id="hidden-task-status" value="{{ $currentStatus }}">
    <input type="hidden" name="category" id="hidden-task-category" value="{{ $currentCategory }}">
    <input type="hidden" name="subtasks" id="hidden-task-subtasks" value="{{ json_encode($initialSubtasks) }}">
    <input type="hidden" name="tags" id="hidden-task-tags" value="{{ json_encode($initialTags) }}">

    {{-- =========================================================
         1. TITLE + INLINE AI SUGGEST
    ========================================================== --}}
    <div class="space-y-1.5">
        <div class="flex items-center justify-between">
            <label for="task-title-input" class="flex items-center gap-1.5 text-xs font-bold text-slate-800 dark:text-slate-200">
                <span class="flex h-5 w-5 items-center justify-center rounded-md bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400">
                    <x-icon name="clipboard" class="h-3 w-3" />
                </span>
                <span>Task Title</span>
                <span class="text-rose-500">*</span>
            </label>

            {{-- Compact AI Suggest Trigger --}}
            <button type="button"
                id="btn-small-form-ai-suggest"
                class="inline-flex items-center gap-1 rounded-lg border border-purple-200/80 bg-purple-50/70 px-2 py-0.5 text-[11px] font-bold text-purple-700 shadow-2xs transition hover:bg-purple-100 hover:border-purple-300 dark:border-purple-900/50 dark:bg-purple-950/40 dark:text-purple-300"
                title="Auto-detect priority and category">
                <x-icon name="sparkles" class="h-3 w-3 text-purple-600 dark:text-purple-400" />
                <span>✨ AI Suggest</span>
            </button>
        </div>

        <input
            id="task-title-input"
            type="text"
            name="title"
            value="{{ old('title', $task->title ?? '') }}"
            placeholder="e.g. Prepare the project brief, plan the next milestone..."
            required
            autocomplete="off"
            class="h-10 w-full rounded-xl border bg-white px-3.5 text-xs sm:text-sm font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 placeholder:font-normal hover:border-slate-300 focus:border-blue-500 focus:ring-3 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:hover:border-slate-600 dark:focus:ring-blue-900/40 {{ $errors->has('title') ? 'border-rose-300 bg-rose-50/30' : 'border-slate-200' }}"
        >

        @error('title')
            <p class="flex items-center gap-1 text-[11px] font-medium text-rose-600 dark:text-rose-400">
                <span class="h-1 w-1 rounded-full bg-rose-500"></span>
                <span>{{ $message }}</span>
            </p>
        @enderror
    </div>

    {{-- =========================================================
         2. DESCRIPTION (COMPACT)
    ========================================================== --}}
    <div class="space-y-1.5">
        <div class="flex items-center justify-between">
            <label for="task-desc-input" class="flex items-center gap-1.5 text-xs font-bold text-slate-800 dark:text-slate-200">
                <span class="flex h-5 w-5 items-center justify-center rounded-md bg-purple-50 text-purple-600 dark:bg-purple-950/50 dark:text-purple-400">
                    <x-icon name="list" class="h-3 w-3" />
                </span>
                <span>Description</span>
            </label>
            <span class="text-[10px] text-slate-400">Optional</span>
        </div>

        <textarea
            id="task-desc-input"
            name="description"
            rows="2"
            placeholder="Add details, notes, or execution steps..."
            class="w-full resize-none rounded-xl border border-slate-200 bg-white p-3 text-xs leading-relaxed text-slate-800 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-blue-500 focus:ring-3 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:placeholder:text-slate-500 dark:focus:ring-blue-900/40"
        >{{ old('description', $task->description ?? '') }}</textarea>
    </div>

    {{-- =========================================================
         3. PRIORITY & STATUS: CLEAN 1-CLICK PILL SWITCHERS
    ========================================================== --}}
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        {{-- Priority Segmented Pills --}}
        <div>
            <p class="mb-1.5 text-xs font-bold text-slate-800 dark:text-slate-200">Priority</p>
            <div class="flex items-center gap-1.5 rounded-xl border border-slate-200/80 bg-slate-50/80 p-1 dark:border-slate-800 dark:bg-slate-800/80" id="priority-pill-selector">
                <button type="button"
                    data-priority-val="low"
                    class="priority-opt-btn flex-1 rounded-lg py-1.5 text-center text-xs font-bold transition {{ $currentPriority === 'low' ? 'bg-white text-emerald-700 shadow-2xs border border-emerald-200 dark:bg-slate-900 dark:text-emerald-400 dark:border-emerald-800' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' }}">
                    🟢 Low
                </button>
                <button type="button"
                    data-priority-val="medium"
                    class="priority-opt-btn flex-1 rounded-lg py-1.5 text-center text-xs font-bold transition {{ $currentPriority === 'medium' ? 'bg-white text-amber-700 shadow-2xs border border-amber-200 dark:bg-slate-900 dark:text-amber-400 dark:border-amber-800' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' }}">
                    🟡 Med
                </button>
                <button type="button"
                    data-priority-val="high"
                    class="priority-opt-btn flex-1 rounded-lg py-1.5 text-center text-xs font-bold transition {{ $currentPriority === 'high' ? 'bg-white text-rose-700 shadow-2xs border border-rose-200 dark:bg-slate-900 dark:text-rose-400 dark:border-rose-800' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' }}">
                    🔴 High
                </button>
            </div>
        </div>

        {{-- Status Segmented Pills (Matching Mockup Segmented Bar) --}}
        <div>
            <p class="mb-1.5 text-xs font-bold text-slate-800 dark:text-slate-200">Status</p>
            <div class="flex items-center gap-1.5 rounded-xl border border-slate-200/80 bg-slate-50/80 p-1 dark:border-slate-800 dark:bg-slate-800/80" id="status-pill-selector">
                <button type="button"
                    data-status-val="pending"
                    class="status-opt-btn flex-1 rounded-lg py-1.5 text-center text-xs font-bold transition {{ $currentStatus === 'pending' ? 'bg-white text-slate-800 shadow-2xs border border-slate-200 dark:bg-slate-900 dark:text-white dark:border-slate-700' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' }}">
                    Pending
                </button>
                <button type="button"
                    data-status-val="in_progress"
                    class="status-opt-btn flex-1 rounded-lg py-1.5 text-center text-xs font-bold transition {{ $currentStatus === 'in_progress' ? 'bg-white text-blue-600 shadow-2xs border border-blue-200 dark:bg-slate-900 dark:text-blue-400 dark:border-blue-800' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' }}">
                    In Progress
                </button>
                <button type="button"
                    data-status-val="completed"
                    class="status-opt-btn flex-1 rounded-lg py-1.5 text-center text-xs font-bold transition {{ $currentStatus === 'completed' ? 'bg-white text-emerald-600 shadow-2xs border border-emerald-200 dark:bg-slate-900 dark:text-emerald-400 dark:border-emerald-800' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' }}">
                    Completed
                </button>
            </div>
        </div>
    </div>

    {{-- =========================================================
         4. CATEGORY PILLS (1-CLICK SELECTOR)
    ========================================================== --}}
    <div>
        <p class="mb-1.5 text-xs font-bold text-slate-800 dark:text-slate-200">Category</p>
        <div class="flex flex-wrap items-center gap-1.5" id="category-pills-container">
            @foreach($categoriesList as $cat)
                @php
                    $isSelected = ($currentCategory === $cat['name']);
                @endphp
                <button type="button"
                    data-category-val="{{ $cat['name'] }}"
                    class="cat-opt-pill inline-flex items-center gap-1 rounded-lg border px-2.5 py-1 text-xs font-bold transition {{ $isSelected ? $cat['color'] . ' shadow-2xs ring-2 ring-blue-500/20' : 'border-slate-200/90 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-750' }}">
                    <span>{{ $cat['name'] }}</span>
                </button>
            @endforeach
        </div>
    </div>

    {{-- =========================================================
         5. DUE DATE & END DATE (COMPACT 2-COLUMN ROW)
    ========================================================== --}}
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        {{-- Due Date --}}
        <div>
            <div class="mb-1 flex items-center justify-between">
                <label for="task-due-date" class="flex items-center gap-1 text-xs font-bold text-slate-800 dark:text-slate-200">
                    <x-icon name="calendar" class="h-3 w-3 text-slate-400" />
                    <span>Due Date</span>
                </label>
                {{-- Quick Date Preset Buttons --}}
                <div class="flex items-center gap-1">
                    <button type="button" data-set-date-preset="today" class="rounded px-1.5 py-0.5 text-[10px] font-semibold text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-950/40">Today</button>
                    <button type="button" data-set-date-preset="tomorrow" class="rounded px-1.5 py-0.5 text-[10px] font-semibold text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-950/40">Tomorrow</button>
                </div>
            </div>
            <input
                id="task-due-date"
                type="date"
                name="due_date"
                value="{{ old('due_date', isset($task->due_date) ? $task->due_date->format('Y-m-d') : '') }}"
                class="h-9 w-full rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-800 outline-none transition hover:border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:focus:ring-blue-900/40"
            >
        </div>

        {{-- End Date --}}
        <div>
            <label for="task-end-date" class="mb-1 flex items-center gap-1 text-xs font-bold text-slate-800 dark:text-slate-200">
                <x-icon name="clock" class="h-3 w-3 text-slate-400" />
                <span>End Date</span>
                <span class="text-[10px] font-normal text-slate-400">(Optional)</span>
            </label>
            <input
                id="task-end-date"
                type="date"
                name="end_date"
                value="{{ old('end_date', isset($task->end_date) ? $task->end_date->format('Y-m-d') : '') }}"
                class="h-9 w-full rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-800 outline-none transition hover:border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:focus:ring-blue-900/40"
            >
        </div>
    </div>

    {{-- =========================================================
         6. CHECKLIST BUILDER (CLEAN, COMPACT MATCHING SCREENSHOT)
    ========================================================== --}}
    <div class="rounded-2xl border border-slate-200/80 bg-slate-50/50 p-3.5 dark:border-slate-800 dark:bg-slate-800/40" id="form-checklist-builder">
        <div class="flex items-center justify-between pb-2.5">
            <div class="flex items-center gap-2">
                <x-icon name="check-circle" class="h-3.5 w-3.5 text-emerald-500" />
                <span class="text-xs font-bold text-slate-900 dark:text-white">Checklist Milestones</span>
                <span id="form-checklist-badge" class="rounded-full bg-slate-200/80 px-1.5 py-0.5 text-[10px] font-bold text-slate-700 dark:bg-slate-700 dark:text-slate-300">
                    <span id="form-subtask-count">{{ count($initialSubtasks) }}</span> steps
                </span>
            </div>

            <button type="button"
                id="btn-small-form-ai-breakdown"
                class="inline-flex items-center gap-1 rounded-lg border border-purple-200/80 bg-purple-50/80 px-2 py-0.5 text-[11px] font-bold text-purple-700 shadow-2xs transition hover:bg-purple-100 hover:border-purple-300 dark:border-purple-900/50 dark:bg-purple-950/40 dark:text-purple-300"
                title="Generate smart milestones using AI">
                <x-icon name="sparkles" class="h-3 w-3 text-purple-600 dark:text-purple-400" />
                <span>AI Breakdown</span>
            </button>
        </div>

        {{-- Dynamic Items Container --}}
        <div class="space-y-1.5" id="form-subtasks-items-list">
            @forelse($initialSubtasks as $idx => $st)
                @php
                    $palette = $subtaskIconPalette[$idx % count($subtaskIconPalette)];
                    $stTitle = is_array($st) ? ($st['title'] ?? '') : (string) $st;
                    $stId = is_array($st) ? ($st['id'] ?? 'st_' . $idx) : 'st_' . $idx;
                @endphp
                <div class="group flex items-center justify-between gap-2 rounded-xl border border-slate-200/70 bg-white p-2 text-xs shadow-2xs dark:border-slate-700/60 dark:bg-slate-900" data-form-st-id="{{ $stId }}">
                    <div class="flex items-center gap-2 min-w-0 flex-1">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg {{ $palette['style'] }}">
                            <x-icon :name="$palette['icon']" class="h-3 w-3" />
                        </span>
                        <span class="truncate font-medium text-slate-800 dark:text-slate-200">{{ $stTitle }}</span>
                    </div>
                    <button type="button" class="btn-remove-form-st opacity-60 hover:opacity-100 rounded p-1 text-slate-400 hover:text-rose-600 transition" title="Remove step">
                        ✕
                    </button>
                </div>
            @empty
                <div id="form-subtasks-empty" class="py-2 text-center text-xs text-slate-400">
                    No steps added yet. Type below to add milestones.
                </div>
            @endforelse
        </div>

        {{-- Inline Add Step Input --}}
        <div class="mt-2.5 flex items-center gap-1.5">
            <input type="text"
                id="input-form-new-step"
                placeholder="Add a new checklist step... (Press Enter)"
                class="h-8 flex-1 rounded-lg border border-slate-200 bg-white px-3 text-xs text-slate-800 placeholder:text-slate-400 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-900 dark:text-white">
            <button type="button"
                id="btn-form-add-step"
                class="inline-flex h-8 items-center gap-1 rounded-lg bg-blue-600 px-3 text-xs font-bold text-white shadow-xs hover:bg-blue-700 active:scale-95 transition">
                <x-icon name="plus" class="h-3 w-3" />
                <span>Add Step</span>
            </button>
        </div>
    </div>

    {{-- =========================================================
         7. PIN TO TOP CHECKBOX
    ========================================================== --}}
    <label class="flex cursor-pointer items-center justify-between rounded-xl border border-slate-200/80 bg-white p-2.5 transition hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:hover:bg-slate-800/80">
        <div class="flex items-center gap-2">
            <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-amber-50 text-amber-500 dark:bg-amber-950/60 dark:text-amber-400">
                <x-icon name="pin" class="h-3.5 w-3.5" />
            </span>
            <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Pin to top of list</span>
        </div>
        <input type="checkbox" name="is_pinned" value="1" @checked(old('is_pinned', $task->is_pinned ?? false)) class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
    </label>

</div>

{{-- Inline JS to handle small form interactions cleanly --}}
<script>
(() => {
    const root = document.getElementById('clean-task-small-form');
    if (!root) return;

    // 1. Priority Switcher
    const hiddenPriority = document.getElementById('hidden-task-priority');
    root.querySelectorAll('.priority-opt-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const p = btn.dataset.priorityVal;
            hiddenPriority.value = p;
            root.querySelectorAll('.priority-opt-btn').forEach(b => {
                b.className = 'priority-opt-btn flex-1 rounded-lg py-1.5 text-center text-xs font-bold transition text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white';
            });
            if (p === 'low') {
                btn.className = 'priority-opt-btn flex-1 rounded-lg py-1.5 text-center text-xs font-bold transition bg-white text-emerald-700 shadow-2xs border border-emerald-200 dark:bg-slate-900 dark:text-emerald-400 dark:border-emerald-800';
            } else if (p === 'high') {
                btn.className = 'priority-opt-btn flex-1 rounded-lg py-1.5 text-center text-xs font-bold transition bg-white text-rose-700 shadow-2xs border border-rose-200 dark:bg-slate-900 dark:text-rose-400 dark:border-rose-800';
            } else {
                btn.className = 'priority-opt-btn flex-1 rounded-lg py-1.5 text-center text-xs font-bold transition bg-white text-amber-700 shadow-2xs border border-amber-200 dark:bg-slate-900 dark:text-amber-400 dark:border-amber-800';
            }
        });
    });

    // 2. Status Switcher
    const hiddenStatus = document.getElementById('hidden-task-status');
    root.querySelectorAll('.status-opt-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const s = btn.dataset.statusVal;
            hiddenStatus.value = s;
            root.querySelectorAll('.status-opt-btn').forEach(b => {
                b.className = 'status-opt-btn flex-1 rounded-lg py-1.5 text-center text-xs font-bold transition text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white';
            });
            if (s === 'in_progress') {
                btn.className = 'status-opt-btn flex-1 rounded-lg py-1.5 text-center text-xs font-bold transition bg-white text-blue-600 shadow-2xs border border-blue-200 dark:bg-slate-900 dark:text-blue-400 dark:border-blue-800';
            } else if (s === 'completed') {
                btn.className = 'status-opt-btn flex-1 rounded-lg py-1.5 text-center text-xs font-bold transition bg-white text-emerald-600 shadow-2xs border border-emerald-200 dark:bg-slate-900 dark:text-emerald-400 dark:border-emerald-800';
            } else {
                btn.className = 'status-opt-btn flex-1 rounded-lg py-1.5 text-center text-xs font-bold transition bg-white text-slate-800 shadow-2xs border border-slate-200 dark:bg-slate-900 dark:text-white dark:border-slate-700';
            }
        });
    });

    // 3. Category Switcher
    const hiddenCat = document.getElementById('hidden-task-category');
    root.querySelectorAll('.cat-opt-pill').forEach(btn => {
        btn.addEventListener('click', () => {
            const cat = btn.dataset.categoryVal;
            hiddenCat.value = cat;
            root.querySelectorAll('.cat-opt-pill').forEach(b => {
                b.className = 'cat-opt-pill inline-flex items-center gap-1 rounded-lg border px-2.5 py-1 text-xs font-bold transition border-slate-200/90 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-750';
            });
            btn.className = 'cat-opt-pill inline-flex items-center gap-1 rounded-lg border px-2.5 py-1 text-xs font-bold transition bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/50 dark:text-purple-300 shadow-2xs ring-2 ring-blue-500/20';
        });
    });

    // 4. Quick Date Presets
    root.querySelectorAll('[data-set-date-preset]').forEach(btn => {
        btn.addEventListener('click', () => {
            const dateInput = document.getElementById('task-due-date');
            if (!dateInput) return;
            const now = new Date();
            if (btn.dataset.setDatePreset === 'tomorrow') {
                now.setDate(now.getDate() + 1);
            }
            dateInput.value = now.toISOString().split('T')[0];
        });
    });

    // 5. Checklist Items Manager
    const hiddenSubtasks = document.getElementById('hidden-task-subtasks');
    const itemsList = document.getElementById('form-subtasks-items-list');
    const stepInput = document.getElementById('input-form-new-step');
    const addStepBtn = document.getElementById('btn-form-add-step');
    const emptyHint = document.getElementById('form-subtasks-empty');
    const countBadge = document.getElementById('form-subtask-count');

    let subtasksData = [];
    try {
        subtasksData = JSON.parse(hiddenSubtasks?.value || '[]');
        if (!Array.isArray(subtasksData)) subtasksData = [];
    } catch (_) { subtasksData = []; }

    const iconPalettes = [
        { icon: 'book', style: 'bg-blue-50 text-blue-600 border border-blue-100 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-900/50' },
        { icon: 'layers', style: 'bg-purple-50 text-purple-600 border border-purple-100 dark:bg-purple-950/60 dark:text-purple-300 dark:border-purple-900/50' },
        { icon: 'zap', style: 'bg-emerald-50 text-emerald-600 border border-emerald-100 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-900/50' },
        { icon: 'folder', style: 'bg-amber-50 text-amber-600 border border-amber-100 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-900/50' },
        { icon: 'flask', style: 'bg-rose-50 text-rose-600 border border-rose-100 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-900/50' }
    ];

    function updateSubtasks() {
        hiddenSubtasks.value = JSON.stringify(subtasksData);
        if (countBadge) countBadge.textContent = subtasksData.length;
        if (subtasksData.length === 0) {
            if (emptyHint) emptyHint.classList.remove('hidden');
        } else {
            if (emptyHint) emptyHint.classList.add('hidden');
        }
    }

    function addSubtask(title) {
        if (!title.trim()) return;
        const newId = 'st_' + Date.now().toString(36) + Math.random().toString(36).substr(2, 4);
        subtasksData.push({ id: newId, title: title.trim(), completed: false });
        renderSubtasksUI();
        updateSubtasks();
        stepInput.value = '';
    }

    function renderSubtasksUI() {
        if (!itemsList) return;
        itemsList.innerHTML = '';
        if (subtasksData.length === 0) {
            itemsList.innerHTML = `<div id="form-subtasks-empty" class="py-2 text-center text-xs text-slate-400">No steps added yet. Type below to add milestones.</div>`;
            return;
        }

        subtasksData.forEach((st, idx) => {
            const p = iconPalettes[idx % iconPalettes.length];
            const div = document.createElement('div');
            div.className = 'group flex items-center justify-between gap-2 rounded-xl border border-slate-200/70 bg-white p-2 text-xs shadow-2xs dark:border-slate-700/60 dark:bg-slate-900';
            div.dataset.formStId = st.id;
            div.innerHTML = `
                <div class="flex items-center gap-2 min-w-0 flex-1">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg ${p.style}">
                        <svg viewBox="0 0 24 24" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>
                    </span>
                    <span class="truncate font-medium text-slate-800 dark:text-slate-200">${st.title}</span>
                </div>
                <button type="button" class="btn-remove-form-st opacity-60 hover:opacity-100 rounded p-1 text-slate-400 hover:text-rose-600 transition" title="Remove step">✕</button>
            `;
            div.querySelector('.btn-remove-form-st')?.addEventListener('click', () => {
                subtasksData = subtasksData.filter(s => s.id !== st.id);
                renderSubtasksUI();
                updateSubtasks();
            });
            itemsList.appendChild(div);
        });
    }

    addStepBtn?.addEventListener('click', () => addSubtask(stepInput.value));
    stepInput?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            addSubtask(stepInput.value);
        }
    });

    // 6. AI Suggest in Small Form
    const suggestBtn = document.getElementById('btn-small-form-ai-suggest');
    suggestBtn?.addEventListener('click', async () => {
        const titleInput = document.getElementById('task-title-input');
        const title = titleInput?.value.trim();
        if (!title) {
            titleInput?.focus();
            return;
        }
        suggestBtn.disabled = true;
        suggestBtn.innerHTML = `<span>Analyzing...</span>`;
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch('/tasks/ai/enhance', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                },
                body: JSON.stringify({ title }),
            });
            if (res.ok) {
                const data = await res.json();
                if (data.priority) {
                    const pBtn = root.querySelector(`[data-priority-val="${data.priority}"]`);
                    pBtn?.click();
                }
                if (data.category) {
                    const cBtn = root.querySelector(`[data-category-val="${data.category}"]`);
                    cBtn?.click();
                }
            }
        } catch (_) {}
        finally {
            suggestBtn.disabled = false;
            suggestBtn.innerHTML = `<span>✨ AI Suggest</span>`;
        }
    });

    // 7. AI Breakdown in Small Form
    const breakdownBtn = document.getElementById('btn-small-form-ai-breakdown');
    breakdownBtn?.addEventListener('click', async () => {
        const titleInput = document.getElementById('task-title-input');
        const title = titleInput?.value.trim();
        if (!title) {
            titleInput?.focus();
            return;
        }
        breakdownBtn.disabled = true;
        breakdownBtn.innerHTML = `<span>Generating...</span>`;
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch('/tasks/ai/breakdown', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                },
                body: JSON.stringify({
                    title,
                    description: document.getElementById('task-desc-input')?.value || null,
                    category: hiddenCat?.value || null,
                }),
            });
            if (res.ok) {
                const data = await res.json();
                if (data.subtasks && Array.isArray(data.subtasks)) {
                    data.subtasks.forEach(s => {
                        const sTitle = typeof s === 'string' ? s : (s.title || '');
                        if (sTitle) {
                            subtasksData.push({
                                id: 'st_' + Date.now().toString(36) + Math.random().toString(36).substr(2, 4),
                                title: sTitle,
                                completed: false
                            });
                        }
                    });
                    renderSubtasksUI();
                    updateSubtasks();
                }
            }
        } catch (_) {}
        finally {
            breakdownBtn.disabled = false;
            breakdownBtn.innerHTML = `<span>AI Breakdown</span>`;
        }
    });
})();
</script>
