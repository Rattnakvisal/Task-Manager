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

        $taskIcons = [
            'clipboard',
            'activity',
            'list',
            'users',
        ];

        $taskIconColors = [
            'bg-blue-50 text-blue-600',
            'bg-violet-50 text-violet-600',
            'bg-amber-50 text-amber-600',
            'bg-emerald-50 text-emerald-600',
        ];

        $hasFilters = request('q') || request('status') || request('priority');
    @endphp


    <div class="mx-auto max-w-[1500px]">

        {{-- =========================================================
            HERO HEADER
        ========================================================== --}}
        <section
            class="relative mb-6 overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm">

            <div
                class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-blue-100/60 blur-3xl">
            </div>

            <div
                class="pointer-events-none absolute right-56 top-10 h-32 w-32 rounded-full bg-indigo-100/50 blur-3xl">
            </div>


            <div
                class="relative flex flex-col gap-6 px-6 py-7 sm:px-8 lg:flex-row lg:items-center lg:justify-between">

                <div>

                    <div class="mb-3 flex items-center gap-2">

                        <span
                            class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-700">

                            <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>

                            All Work
                        </span>

                    </div>


                    <h1
                        class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">
                        All Tasks
                    </h1>


                    <p
                        class="mt-3 max-w-xl text-sm leading-6 text-slate-500 sm:text-base">
                        Search, filter and manage every task in your workspace from
                        one organized view.
                    </p>

                </div>


                <div class="flex flex-wrap gap-3">

                    <a
                        href="{{ route('dashboard') }}"
                        class="
                            inline-flex h-11 items-center justify-center gap-2
                            rounded-xl border border-slate-200 bg-white px-4
                            text-sm font-semibold text-slate-600 shadow-sm
                            transition hover:bg-slate-50 hover:text-slate-900
                        "
                    >
                        <x-icon name="grid" class="h-4 w-4" />

                        Dashboard
                    </a>


                    <a
                        href="{{ route('tasks.index') }}#new-task"
                        data-open-task-modal
                        class="
                            inline-flex h-11 items-center justify-center gap-2
                            rounded-xl bg-blue-950 px-5
                            text-sm font-semibold text-white
                            shadow-lg shadow-blue-950/10
                            transition duration-200
                            hover:-translate-y-0.5 hover:bg-blue-900
                        "
                    >
                        <x-icon name="plus" class="h-4 w-4" />

                        Add Task
                    </a>

                </div>

            </div>

        </section>



        {{-- =========================================================
            TASK MANAGER CARD
        ========================================================== --}}
        <section
            class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">

            {{-- =====================================================
                TOP TOOLBAR
            ====================================================== --}}
            <div
                class="border-b border-slate-100 px-5 py-5 sm:px-6">

                <div
                    class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">

                    {{-- Left Title --}}
                    <div>

                        <div class="flex items-center gap-3">

                            <span
                                class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600">

                                <x-icon name="list" class="h-5 w-5" />

                            </span>


                            <div>

                                <h2 class="font-bold text-slate-950">
                                    Task Database
                                </h2>

                                <p class="mt-0.5 text-xs text-slate-500">
                                    Browse and manage all tasks
                                </p>

                            </div>

                        </div>

                    </div>



                    {{-- Filters --}}
                    <form
                        method="GET"
                        action="{{ route('all-tasks') }}"
                        class="flex w-full flex-col gap-3 md:flex-row xl:w-auto">

                        {{-- Search --}}
                        <div class="relative flex-1 xl:w-72">

                            <x-icon
                                name="search"
                                class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                            />

                            <input
                                type="search"
                                name="q"
                                value="{{ request('q') }}"
                                placeholder="Search tasks..."
                                class="
                                    h-11 w-full rounded-xl
                                    border border-slate-200 bg-white
                                    pl-10 pr-4 text-sm text-slate-700
                                    outline-none transition
                                    placeholder:text-slate-400
                                    hover:border-slate-300
                                    focus:border-blue-500
                                    focus:ring-4 focus:ring-blue-100
                                "
                            >

                        </div>



                        {{-- Status --}}
                        <div class="relative md:w-44">

                            <span
                                class="pointer-events-none absolute left-3.5 top-1/2 h-2 w-2 -translate-y-1/2 rounded-full bg-blue-500">
                            </span>

                            <select
                                name="status"
                                class="
                                    h-11 w-full appearance-none rounded-xl
                                    border border-slate-200 bg-white
                                    pl-8 pr-9 text-sm font-medium text-slate-600
                                    outline-none transition
                                    hover:border-slate-300
                                    focus:border-blue-500
                                    focus:ring-4 focus:ring-blue-100
                                "
                            >
                                <option value="">All Status</option>

                                <option
                                    value="pending"
                                    @selected(request('status') === 'pending')
                                >
                                    Pending
                                </option>

                                <option
                                    value="in_progress"
                                    @selected(request('status') === 'in_progress')
                                >
                                    In Progress
                                </option>

                                <option
                                    value="completed"
                                    @selected(request('status') === 'completed')
                                >
                                    Completed
                                </option>
                            </select>


                            <svg
                                viewBox="0 0 20 20"
                                fill="currentColor"
                                class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z"
                                    clip-rule="evenodd"
                                />
                            </svg>

                        </div>



                        {{-- Priority --}}
                        <div class="relative md:w-44">

                            <x-icon
                                name="flag"
                                class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                            />

                            <select
                                name="priority"
                                class="
                                    h-11 w-full appearance-none rounded-xl
                                    border border-slate-200 bg-white
                                    pl-10 pr-9 text-sm font-medium text-slate-600
                                    outline-none transition
                                    hover:border-slate-300
                                    focus:border-blue-500
                                    focus:ring-4 focus:ring-blue-100
                                "
                            >
                                <option value="">All Priority</option>

                                <option
                                    value="high"
                                    @selected(request('priority') === 'high')
                                >
                                    High
                                </option>

                                <option
                                    value="medium"
                                    @selected(request('priority') === 'medium')
                                >
                                    Medium
                                </option>

                                <option
                                    value="low"
                                    @selected(request('priority') === 'low')
                                >
                                    Low
                                </option>
                            </select>


                            <svg
                                viewBox="0 0 20 20"
                                fill="currentColor"
                                class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z"
                                    clip-rule="evenodd"
                                />
                            </svg>

                        </div>



                        {{-- Search Button --}}
                        <button
                            type="submit"
                            class="
                                inline-flex h-11 items-center justify-center gap-2
                                rounded-xl bg-blue-950 px-5
                                text-sm font-semibold text-white
                                shadow-sm transition hover:bg-blue-900
                            "
                        >
                            <x-icon name="search" class="h-4 w-4" />

                            Search
                        </button>

                    </form>

                </div>


                {{-- Active Filters --}}
                @if ($hasFilters)

                    <div
                        class="mt-4 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-4">

                        <span class="text-xs font-semibold text-slate-400">
                            Active filters:
                        </span>


                        @if (request('q'))

                            <span
                                class="inline-flex items-center rounded-lg bg-slate-100 px-2.5 py-1.5 text-xs font-semibold text-slate-600">

                                Search: "{{ request('q') }}"

                            </span>

                        @endif


                        @if (request('status'))

                            <span
                                class="inline-flex items-center rounded-lg bg-blue-50 px-2.5 py-1.5 text-xs font-semibold text-blue-700">

                                {{ ucfirst(str_replace('_', ' ', request('status'))) }}

                            </span>

                        @endif


                        @if (request('priority'))

                            <span
                                class="inline-flex items-center rounded-lg bg-amber-50 px-2.5 py-1.5 text-xs font-semibold text-amber-700">

                                {{ ucfirst(request('priority')) }} Priority

                            </span>

                        @endif


                        <a
                            href="{{ route('all-tasks') }}"
                            class="ml-1 text-xs font-bold text-rose-500 transition hover:text-rose-700"
                        >
                            Clear all
                        </a>

                    </div>

                @endif

            </div>



            {{-- =====================================================
                EMPTY STATE
            ====================================================== --}}
            @if ($tasks->isEmpty())

                <div
                    class="flex min-h-[430px] flex-col items-center justify-center px-6 py-16 text-center">

                    <div
                        class="mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">

                        <x-icon name="inbox" class="h-7 w-7" />

                    </div>


                    <h2 class="text-lg font-bold text-slate-950">
                        No tasks found
                    </h2>


                    <p
                        class="mt-2 max-w-sm text-sm leading-6 text-slate-500">
                        We couldn't find a task matching your current search or
                        filters.
                    </p>


                    <div
                        class="mt-6 flex flex-wrap items-center justify-center gap-3">

                        @if ($hasFilters)

                            <a
                                href="{{ route('all-tasks') }}"
                                class="
                                    inline-flex h-10 items-center justify-center
                                    rounded-xl border border-slate-200
                                    bg-white px-4 text-sm font-semibold
                                    text-slate-600 shadow-sm
                                    transition hover:bg-slate-50
                                "
                            >
                                Clear Filters
                            </a>

                        @endif


                        <a
                            href="{{ route('tasks.index') }}#new-task"
                            data-open-task-modal
                            class="
                                inline-flex h-10 items-center justify-center
                                gap-2 rounded-xl bg-blue-950 px-4
                                text-sm font-semibold text-white
                                transition hover:bg-blue-900
                            "
                        >
                            <x-icon name="plus" class="h-4 w-4" />

                            Create Task
                        </a>

                    </div>

                </div>


            @else

                {{-- =================================================
                    DESKTOP TABLE HEADER
                ================================================== --}}
                <div
                    class="
                        hidden grid-cols-[minmax(0,1fr)_130px_155px_100px]
                        gap-4 border-b border-slate-100
                        bg-slate-50/60 px-6 py-3
                        text-[10px] font-bold uppercase
                        tracking-[0.15em] text-slate-400
                        lg:grid
                    "
                >
                    <span>Task Details</span>

                    <span>Priority</span>

                    <span>Status</span>

                    <span class="text-right">
                        Actions
                    </span>
                </div>



                {{-- =================================================
                    TASK ROWS
                ================================================== --}}
                <div class="divide-y divide-slate-100">

                    @forelse ($tasks as $task)

                        <div
                            class="
                                group grid gap-4
                                px-5 py-5 transition duration-200
                                hover:bg-slate-50/70
                                sm:px-6
                                lg:grid-cols-[minmax(0,1fr)_130px_155px_100px]
                                lg:items-center
                            "
                        >

                            {{-- =========================================
                                TASK CONTENT
                            ========================================== --}}
                            <div class="flex min-w-0 items-start gap-4">

                                <span
                                    class="
                                        flex h-11 w-11 shrink-0
                                        items-center justify-center
                                        rounded-xl
                                        {{ $taskIconColors[
                                            $loop->index % count($taskIconColors)
                                        ] }}
                                    "
                                >
                                    <x-icon
                                        :name="$taskIcons[
                                            $loop->index % count($taskIcons)
                                        ]"
                                        class="h-5 w-5"
                                    />
                                </span>


                                <div class="min-w-0 flex-1">

                                    <p
                                        class="truncate text-sm font-bold text-slate-950 sm:text-base">
                                        {{ $task->title }}
                                    </p>


                                    @if ($task->description)

                                        <p
                                            class="mt-1.5 line-clamp-2 max-w-2xl text-sm leading-6 text-slate-500">
                                            {{ $task->description }}
                                        </p>

                                    @else

                                        <p class="mt-1.5 text-xs italic text-slate-400">
                                            No description added
                                        </p>

                                    @endif



                                    {{-- Due date --}}
                                    <div
                                        class="mt-2.5 flex flex-wrap items-center gap-3">

                                        @if ($task->due_date)

                                            <span
                                                class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-400">

                                                <x-icon
                                                    name="calendar"
                                                    class="h-3.5 w-3.5"
                                                />

                                                Due
                                                {{ $task->due_date->format('M d, Y') }}
                                                @if ($task->end_date)
                                                    - End {{ $task->end_date->format('M d, Y') }}
                                                @endif

                                            </span>

                                        @else

                                            <span
                                                class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-400">

                                                <x-icon
                                                    name="calendar"
                                                    class="h-3.5 w-3.5"
                                                />

                                                No due date

                                            </span>

                                        @endif

                                    </div>

                                </div>

                            </div>



                            {{-- =========================================
                                PRIORITY
                            ========================================== --}}
                            <div>

                                <span
                                    class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-400 lg:hidden">
                                    Priority
                                </span>


                                <span
                                    class="
                                        inline-flex w-fit items-center
                                        gap-1.5 rounded-lg px-2.5 py-1.5
                                        text-xs font-bold
                                        ring-1 ring-inset
                                        {{ $priorityColors[$task->priority] }}
                                    "
                                >
                                    <span
                                        class="
                                            h-1.5 w-1.5 rounded-full
                                            {{ $priorityDots[$task->priority] }}
                                        "
                                    ></span>

                                    {{ ucfirst($task->priority) }}

                                </span>

                            </div>



                            {{-- =========================================
                                STATUS
                            ========================================== --}}
                            <div>

                                <span
                                    class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-400 lg:hidden">
                                    Status
                                </span>


                                <span
                                    class="
                                        inline-flex w-fit rounded-lg
                                        px-2.5 py-1.5
                                        text-xs font-bold
                                        ring-1 ring-inset
                                        {{ $statusColors[$task->status] }}
                                    "
                                >
                                    {{ ucfirst(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $task->status
                                        )
                                    ) }}
                                </span>

                            </div>



                            {{-- =========================================
                                ACTIONS
                            ========================================== --}}
                            <div
                                class="flex items-center gap-2 lg:justify-end">

                                {{-- Edit --}}
                                <a
                                    href="{{ route('tasks.edit', $task) }}"
                                    class="
                                        inline-flex h-9 w-9
                                        items-center justify-center
                                        rounded-lg border
                                        border-slate-200 bg-white
                                        text-slate-500
                                        transition duration-200
                                        hover:border-blue-200
                                        hover:bg-blue-50
                                        hover:text-blue-600
                                    "
                                    title="Edit task"
                                    aria-label="Edit {{ $task->title }}"
                                >
                                    <x-icon name="edit" class="h-4 w-4" />
                                </a>


                                {{-- Delete --}}
                                <form
                                    method="POST"
                                    action="{{ route('tasks.destroy', $task) }}"
                                    onsubmit="return confirm('Are you sure you want to delete this task?');"
                                >
                                    @csrf
                                    @method('DELETE')


                                    <button
                                        type="submit"
                                        class="
                                            inline-flex h-9 w-9
                                            items-center justify-center
                                            rounded-lg border
                                            border-rose-100 bg-white
                                            text-rose-500
                                            transition duration-200
                                            hover:border-rose-200
                                            hover:bg-rose-50
                                            hover:text-rose-600
                                        "
                                        title="Delete task"
                                        aria-label="Delete {{ $task->title }}"
                                    >
                                        <x-icon
                                            name="trash"
                                            class="h-4 w-4"
                                        />
                                    </button>

                                </form>

                            </div>

                        </div>

                    @empty
                    @endforelse

                </div>



                {{-- =================================================
                    FOOTER
                ================================================== --}}
                <div
                    class="
                        flex flex-col gap-3
                        border-t border-slate-100
                        bg-slate-50/50
                        px-5 py-4
                        sm:flex-row
                        sm:items-center
                        sm:justify-between
                        sm:px-6
                    "
                >

                    <p class="text-xs font-medium text-slate-400">

                        Showing

                        <span class="font-bold text-slate-700">
                            {{ $tasks->count() }}
                        </span>

                        {{ Str::plural('task', $tasks->count()) }}

                    </p>


                    <a
                        href="{{ route('tasks.index') }}#new-task"
                        data-open-task-modal
                        class="
                            inline-flex items-center gap-1.5
                            text-xs font-bold text-blue-600
                            transition hover:text-blue-800
                        "
                    >
                        <x-icon name="plus" class="h-3.5 w-3.5" />

                        Add another task
                    </a>

                </div>

            @endif

        </section>

    </div>
@endsection
