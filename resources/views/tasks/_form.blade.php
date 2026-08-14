@php
    $task = $task ?? null;
@endphp

<div class="space-y-5">

    {{-- =========================================================
        TITLE
    ========================================================== --}}
    <div>
        <label for="title"
               class="mb-2 flex items-center gap-2 text-sm font-semibold text-slate-700">
            <span class="flex h-6 w-6 items-center justify-center rounded-md bg-blue-50 text-blue-600">
                <x-icon name="clipboard" class="h-3.5 w-3.5" />
            </span>

            Task Title

            <span class="text-rose-500">*</span>
        </label>

        <input
            id="title"
            type="text"
            name="title"
            value="{{ old('title', $task->title ?? '') }}"
            placeholder="What needs to be done?"
            autocomplete="off"
            required
            class="
                h-12 w-full rounded-xl border bg-white px-4
                text-sm font-medium text-slate-900
                outline-none transition-all duration-200
                placeholder:font-normal placeholder:text-slate-400

                {{ $errors->has('title')
                    ? 'border-rose-300 bg-rose-50/30 focus:border-rose-500 focus:ring-4 focus:ring-rose-100'
                    : 'border-slate-200 hover:border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-100'
                }}
            "
        >

        @error('title')
            <div class="mt-2 flex items-center gap-1.5 text-xs font-medium text-rose-600">
                <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                {{ $message }}
            </div>
        @enderror
    </div>


    {{-- =========================================================
        DESCRIPTION
    ========================================================== --}}
    <div>
        <div class="mb-2 flex items-center justify-between">

            <label for="description"
                   class="flex items-center gap-2 text-sm font-semibold text-slate-700">

                <span class="flex h-6 w-6 items-center justify-center rounded-md bg-violet-50 text-violet-600">
                    <x-icon name="list" class="h-3.5 w-3.5" />
                </span>

                Description
            </label>

            <span class="text-[11px] font-medium text-slate-400">
                Optional
            </span>

        </div>

        <textarea
            id="description"
            name="description"
            rows="4"
            placeholder="Add notes, details, or useful information about this task..."
            autocomplete="off"
            class="
                min-h-[120px] w-full resize-none rounded-xl
                border bg-white px-4 py-3
                text-sm leading-6 text-slate-900
                outline-none transition-all duration-200
                placeholder:text-slate-400

                {{ $errors->has('description')
                    ? 'border-rose-300 bg-rose-50/30 focus:border-rose-500 focus:ring-4 focus:ring-rose-100'
                    : 'border-slate-200 hover:border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-100'
                }}
            "
        >{{ old('description', $task->description ?? '') }}</textarea>

        @error('description')
            <div class="mt-2 flex items-center gap-1.5 text-xs font-medium text-rose-600">
                <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                {{ $message }}
            </div>
        @enderror
    </div>


    {{-- =========================================================
        PRIORITY + STATUS
    ========================================================== --}}
    <div class="grid gap-4 sm:grid-cols-2">

        {{-- Priority --}}
        <div>
            <label for="priority"
                   class="mb-2 flex items-center gap-2 text-sm font-semibold text-slate-700">

                <span class="flex h-6 w-6 items-center justify-center rounded-md bg-amber-50 text-amber-600">
                    <x-icon name="flag" class="h-3.5 w-3.5" />
                </span>

                Priority
            </label>

            <div class="relative">
                <select
                    id="priority"
                    name="priority"
                    class="
                        h-12 w-full appearance-none rounded-xl
                        border border-slate-200 bg-white
                        px-4 pr-10 text-sm font-medium text-slate-800
                        outline-none transition-all duration-200
                        hover:border-slate-300
                        focus:border-blue-500
                        focus:ring-4 focus:ring-blue-100
                    "
                >
                    @foreach (['low', 'medium', 'high'] as $p)
                        <option
                            value="{{ $p }}"
                            @selected(old('priority', $task->priority ?? 'medium') === $p)
                        >
                            {{ ucfirst($p) }}
                        </option>
                    @endforeach
                </select>

                <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400">
                    <svg viewBox="0 0 20 20"
                         fill="currentColor"
                         class="h-4 w-4">
                        <path fill-rule="evenodd"
                              d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z"
                              clip-rule="evenodd" />
                    </svg>
                </span>
            </div>

            @error('priority')
                <p class="mt-2 text-xs font-medium text-rose-600">
                    {{ $message }}
                </p>
            @enderror
        </div>


        {{-- Status --}}
        <div>
            <label for="status"
                   class="mb-2 flex items-center gap-2 text-sm font-semibold text-slate-700">

                <span class="flex h-6 w-6 items-center justify-center rounded-md bg-blue-50 text-blue-600">
                    <x-icon name="check-circle" class="h-3.5 w-3.5" />
                </span>

                Status
            </label>

            <div class="relative">
                <select
                    id="status"
                    name="status"
                    class="
                        h-12 w-full appearance-none rounded-xl
                        border border-slate-200 bg-white
                        px-4 pr-10 text-sm font-medium text-slate-800
                        outline-none transition-all duration-200
                        hover:border-slate-300
                        focus:border-blue-500
                        focus:ring-4 focus:ring-blue-100
                    "
                >
                    @foreach ([
                        'pending' => 'Pending',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed'
                    ] as $value => $label)

                        <option
                            value="{{ $value }}"
                            @selected(old('status', $task->status ?? 'pending') === $value)
                        >
                            {{ $label }}
                        </option>

                    @endforeach
                </select>

                <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400">
                    <svg viewBox="0 0 20 20"
                         fill="currentColor"
                         class="h-4 w-4">
                        <path fill-rule="evenodd"
                              d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z"
                              clip-rule="evenodd" />
                    </svg>
                </span>
            </div>

            @error('status')
                <p class="mt-2 text-xs font-medium text-rose-600">
                    {{ $message }}
                </p>
            @enderror
        </div>

    </div>


    {{-- =========================================================
        DUE DATE + END DATE
    ========================================================== --}}
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <div class="mb-2 flex items-center justify-between">

                <label for="due_date"
                       class="flex items-center gap-2 text-sm font-semibold text-slate-700">

                    <span class="flex h-6 w-6 items-center justify-center rounded-md bg-emerald-50 text-emerald-600">
                        <x-icon name="calendar" class="h-3.5 w-3.5" />
                    </span>

                    Due Date
                </label>

                <span class="text-[11px] font-medium text-slate-400">
                    Optional
                </span>

            </div>

            <input
                id="due_date"
                type="date"
                name="due_date"
                value="{{ old('due_date', isset($task->due_date) ? $task->due_date->format('Y-m-d') : '') }}"
                class="
                    h-12 w-full rounded-xl border bg-white px-4
                    text-sm font-medium text-slate-800
                    outline-none transition-all duration-200

                    {{ $errors->has('due_date')
                        ? 'border-rose-300 bg-rose-50/30 focus:border-rose-500 focus:ring-4 focus:ring-rose-100'
                        : 'border-slate-200 hover:border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-100'
                    }}
                "
            >

            @error('due_date')
                <div class="mt-2 flex items-center gap-1.5 text-xs font-medium text-rose-600">
                    <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                    {{ $message }}
                </div>
            @enderror
        </div>

        <div>
            <div class="mb-2 flex items-center justify-between">

                <label for="end_date"
                       class="flex items-center gap-2 text-sm font-semibold text-slate-700">

                    <span class="flex h-6 w-6 items-center justify-center rounded-md bg-cyan-50 text-cyan-600">
                        <x-icon name="calendar" class="h-3.5 w-3.5" />
                    </span>

                    End Date
                </label>

                <span class="text-[11px] font-medium text-slate-400">
                    Optional
                </span>

            </div>

            <input
                id="end_date"
                type="date"
                name="end_date"
                value="{{ old('end_date', isset($task->end_date) ? $task->end_date->format('Y-m-d') : '') }}"
                class="
                    h-12 w-full rounded-xl border bg-white px-4
                    text-sm font-medium text-slate-800
                    outline-none transition-all duration-200

                    {{ $errors->has('end_date')
                        ? 'border-rose-300 bg-rose-50/30 focus:border-rose-500 focus:ring-4 focus:ring-rose-100'
                        : 'border-slate-200 hover:border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-100'
                    }}
                "
            >

            @error('end_date')
                <div class="mt-2 flex items-center gap-1.5 text-xs font-medium text-rose-600">
                    <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>


    {{-- =========================================================
        SMALL INFO CARD
    ========================================================== --}}
    <div class="rounded-xl border border-blue-100 bg-blue-50/60 p-3.5">

        <div class="flex items-start gap-3">

            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white text-blue-600 shadow-sm">
                <x-icon name="clipboard" class="h-4 w-4" />
            </span>

            <div>
                <p class="text-xs font-bold text-blue-900">
                    Task details
                </p>

                <p class="mt-1 text-xs leading-5 text-blue-700/70">
                    Add a clear title, select its priority and status,
                    then choose a deadline if needed.
                </p>
            </div>

        </div>

    </div>

</div>
