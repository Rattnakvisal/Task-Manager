@extends('layouts.app')

@section('content')
    @php
        $activeStatus = $activeStatus ?? request('status');
        $activePriority = $activePriority ?? request('priority');

        $page = $page ?? [
            'section' => 'my-tasks',
            'title' => 'My Tasks',
            'subtitle' => 'Plan, organize, and track everything you need to get done.',
            'eyebrow' => 'Workspace',
        ];

        $sectionRoutes = [
            'dashboard' => 'dashboard',
            'calendar' => 'calendar',
            'priority' => 'priority',
            'all-tasks' => 'all-tasks',
            'my-tasks' => 'tasks.index',
        ];

        $sectionRoute = $sectionRoutes[$page['section']] ?? 'tasks.index';

        $createdTaskId = session('created_task_id');

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
            'activity',
            'list',
            'users',
            'clipboard',
        ];

        $taskIconColors = [
            'bg-blue-50 text-blue-600',
            'bg-amber-50 text-amber-600',
            'bg-violet-50 text-violet-600',
            'bg-emerald-50 text-emerald-600',
        ];
    @endphp


    <div class="mx-auto max-w-[1500px]">

        {{-- =========================================================
            PAGE HEADER
        ========================================================== --}}
        <section
            class="relative mb-6 overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm">

            {{-- Background decoration --}}
            <div
                class="pointer-events-none absolute -right-20 -top-24 h-64 w-64 rounded-full bg-blue-100/60 blur-3xl">
            </div>

            <div
                class="pointer-events-none absolute right-48 top-10 h-32 w-32 rounded-full bg-indigo-100/50 blur-3xl">
            </div>


            <div
                class="relative flex flex-col gap-6 px-6 py-7 sm:px-8 lg:flex-row lg:items-center lg:justify-between">

                <div>
                    <div class="mb-3 flex items-center gap-2">

                        <span
                            class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-700">

                            <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>

                            {{ $page['eyebrow'] }}
                        </span>

                    </div>

                    <h1
                        class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">
                        {{ $page['title'] }}
                    </h1>

                    <p
                        class="mt-3 max-w-xl text-sm leading-6 text-slate-500 sm:text-base">
                        {{ $page['subtitle'] }}
                    </p>
                </div>


                <div class="flex flex-wrap gap-3">

                    <a
                        href="{{ route('calendar') }}"
                        class="
                            inline-flex h-11 items-center justify-center gap-2
                            rounded-xl border border-slate-200 bg-white px-4
                            text-sm font-semibold text-slate-600 shadow-sm
                            transition hover:bg-slate-50 hover:text-slate-900
                        "
                    >
                        <x-icon name="calendar" class="h-4 w-4" />

                        Calendar
                    </a>


                    <button
                        type="button"
                        data-open-task-modal
                        class="
                            inline-flex h-11 items-center justify-center gap-2
                            rounded-xl bg-blue-950 px-5 text-sm font-semibold
                            text-white shadow-lg shadow-blue-950/10
                            transition duration-200
                            hover:-translate-y-0.5 hover:bg-blue-900
                        "
                    >
                        <x-icon name="plus" class="h-4 w-4" />

                        New Task
                    </button>

                </div>

            </div>

        </section>



        {{-- =========================================================
            STAT CARDS
        ========================================================== --}}
        <section
            class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">

            @foreach ($stats as $label => $stat)

                <div
                    class="
                        group relative overflow-hidden rounded-2xl
                        border border-slate-200/80 bg-white p-5
                        shadow-sm transition duration-300
                        hover:-translate-y-1 hover:shadow-lg
                        hover:shadow-slate-200/60
                    "
                >

                    <div
                        class="
                            absolute inset-x-0 top-0 h-0.5
                            bg-gradient-to-r from-transparent
                            via-blue-500/50 to-transparent
                            opacity-0 transition group-hover:opacity-100
                        "
                    ></div>


                    <div class="flex items-start justify-between gap-4">

                        <div>
                            <p class="text-sm font-semibold text-slate-500">
                                {{ $label }}
                            </p>

                            <p
                                class="mt-3 text-3xl font-bold tracking-tight text-slate-950">
                                {{ $stat['value'] }}
                            </p>

                            <p class="mt-2 text-xs font-medium text-slate-400">
                                {{ $stat['label'] }}
                            </p>
                        </div>


                        <span
                            class="
                                flex h-11 w-11 items-center justify-center
                                rounded-xl {{ $stat['tone'] }}
                                transition duration-300 group-hover:scale-105
                            "
                        >
                            <x-icon
                                :name="$stat['icon']"
                                class="h-5 w-5"
                            />
                        </span>

                    </div>

                </div>

            @endforeach

        </section>



        {{-- =========================================================
            TASK LIST CARD
        ========================================================== --}}
        <section
            class="
                overflow-hidden rounded-2xl
                border border-slate-200/80
                bg-white shadow-sm
            "
        >

            {{-- =====================================================
                TOOLBAR
            ====================================================== --}}
            <div
                class="
                    border-b border-slate-100
                    px-5 py-5 sm:px-6
                "
            >

                <div
                    class="
                        flex flex-col gap-5
                        xl:flex-row xl:items-center xl:justify-between
                    "
                >

                    {{-- Status Tabs --}}
                    <div
                        class="
                            flex max-w-full gap-1.5 overflow-x-auto
                            rounded-xl bg-slate-50 p-1.5
                            [scrollbar-width:none]
                            [&::-webkit-scrollbar]:hidden
                        "
                    >

                        {{-- All --}}
                        <a
                            href="{{ route($sectionRoute, request()->except('status')) }}"
                            class="
                                inline-flex h-9 shrink-0 items-center gap-2
                                rounded-lg px-4 text-xs font-bold
                                transition

                                {{ !$activeStatus
                                    ? 'bg-white text-blue-700 shadow-sm ring-1 ring-slate-200'
                                    : 'text-slate-500 hover:bg-white hover:text-slate-900'
                                }}
                            "
                        >
                            <x-icon name="clipboard" class="h-3.5 w-3.5" />

                            All
                        </a>


                        {{-- Pending --}}
                        <a
                            href="{{ route(
                                $sectionRoute,
                                array_merge(
                                    request()->except('status'),
                                    ['status' => 'pending']
                                )
                            ) }}"
                            class="
                                inline-flex h-9 shrink-0 items-center gap-2
                                rounded-lg px-4 text-xs font-bold
                                transition

                                {{ $activeStatus === 'pending'
                                    ? 'bg-white text-amber-700 shadow-sm ring-1 ring-slate-200'
                                    : 'text-slate-500 hover:bg-white hover:text-slate-900'
                                }}
                            "
                        >
                            <span class="h-2 w-2 rounded-full bg-amber-400"></span>

                            Pending
                        </a>


                        {{-- In Progress --}}
                        <a
                            href="{{ route(
                                $sectionRoute,
                                array_merge(
                                    request()->except('status'),
                                    ['status' => 'in_progress']
                                )
                            ) }}"
                            class="
                                inline-flex h-9 shrink-0 items-center gap-2
                                rounded-lg px-4 text-xs font-bold
                                transition

                                {{ $activeStatus === 'in_progress'
                                    ? 'bg-white text-blue-700 shadow-sm ring-1 ring-slate-200'
                                    : 'text-slate-500 hover:bg-white hover:text-slate-900'
                                }}
                            "
                        >
                            <span class="h-2 w-2 rounded-full bg-blue-500"></span>

                            In Progress
                        </a>


                        {{-- Completed --}}
                        <a
                            href="{{ route(
                                $sectionRoute,
                                array_merge(
                                    request()->except('status'),
                                    ['status' => 'completed']
                                )
                            ) }}"
                            class="
                                inline-flex h-9 shrink-0 items-center gap-2
                                rounded-lg px-4 text-xs font-bold
                                transition

                                {{ $activeStatus === 'completed'
                                    ? 'bg-white text-emerald-700 shadow-sm ring-1 ring-slate-200'
                                    : 'text-slate-500 hover:bg-white hover:text-slate-900'
                                }}
                            "
                        >
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>

                            Completed
                        </a>

                    </div>



                    {{-- Search + Filter --}}
                    <form
                        method="GET"
                        action="{{ route($sectionRoute) }}"
                        class="
                            flex w-full flex-col gap-3
                            sm:flex-row xl:w-auto
                        "
                    >

                        @if ($activeStatus)
                            <input
                                type="hidden"
                                name="status"
                                value="{{ $activeStatus }}"
                            >
                        @endif


                        {{-- Search --}}
                        <div class="relative flex-1 xl:w-72">

                            <x-icon
                                name="search"
                                class="
                                    pointer-events-none absolute
                                    left-3.5 top-1/2 h-4 w-4
                                    -translate-y-1/2 text-slate-400
                                "
                            />

                            <input
                                type="search"
                                name="q"
                                value="{{ request('q') }}"
                                placeholder="Search your tasks..."
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


                        {{-- Priority filter --}}
                        <div class="relative sm:w-44">

                            <x-icon
                                name="filter"
                                class="
                                    pointer-events-none absolute
                                    left-3.5 top-1/2 h-4 w-4
                                    -translate-y-1/2 text-slate-400
                                "
                            />

                            <select
                                name="priority"
                                onchange="this.form.submit()"
                                class="
                                    h-11 w-full appearance-none
                                    rounded-xl border border-slate-200
                                    bg-white pl-10 pr-9
                                    text-sm font-medium text-slate-600
                                    outline-none transition
                                    hover:border-slate-300
                                    focus:border-blue-500
                                    focus:ring-4 focus:ring-blue-100
                                "
                            >
                                <option value="">
                                    All Priority
                                </option>

                                <option
                                    value="high"
                                    @selected($activePriority === 'high')
                                >
                                    High
                                </option>

                                <option
                                    value="medium"
                                    @selected($activePriority === 'medium')
                                >
                                    Medium
                                </option>

                                <option
                                    value="low"
                                    @selected($activePriority === 'low')
                                >
                                    Low
                                </option>
                            </select>


                            <svg
                                viewBox="0 0 20 20"
                                fill="currentColor"
                                class="
                                    pointer-events-none absolute
                                    right-3 top-1/2 h-4 w-4
                                    -translate-y-1/2 text-slate-400
                                "
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z"
                                    clip-rule="evenodd"
                                />
                            </svg>

                        </div>

                    </form>

                </div>

            </div>



            {{-- =====================================================
                TASK LIST
            ====================================================== --}}
            @if ($tasks->isEmpty())

                {{-- Empty State --}}
                <div
                    class="
                        flex min-h-[430px] flex-col
                        items-center justify-center
                        px-6 py-16 text-center
                    "
                >

                    <div
                        class="
                            mb-5 flex h-16 w-16
                            items-center justify-center
                            rounded-2xl bg-blue-50 text-blue-600
                        "
                    >
                        <x-icon name="inbox" class="h-7 w-7" />
                    </div>


                    <h2
                        class="text-lg font-bold text-slate-950">
                        No tasks found
                    </h2>


                    <p
                        class="
                            mt-2 max-w-sm
                            text-sm leading-6 text-slate-500
                        "
                    >
                        No tasks match your current search or filters.
                        Try changing the filters or create a new task.
                    </p>


                    <div
                        class="
                            mt-6 flex flex-wrap
                            items-center justify-center gap-3
                        "
                    >

                        @if (
                            request('q') ||
                            $activePriority ||
                            $activeStatus
                        )

                            <a
                                href="{{ route($sectionRoute) }}"
                                class="
                                    inline-flex h-10 items-center
                                    rounded-xl border border-slate-200
                                    bg-white px-4 text-sm font-semibold
                                    text-slate-600 transition
                                    hover:bg-slate-50
                                "
                            >
                                Clear Filters
                            </a>

                        @endif


                        <button
                            type="button"
                            data-open-task-modal
                            class="
                                inline-flex h-10 items-center
                                justify-center gap-2
                                rounded-xl bg-blue-950 px-4
                                text-sm font-semibold text-white
                                transition hover:bg-blue-900
                            "
                        >
                            <x-icon
                                name="plus"
                                class="h-4 w-4"
                            />

                            Create Task
                        </button>

                    </div>

                </div>


            @else

                {{-- Desktop heading row --}}
                <div
                    class="
                        hidden grid-cols-[minmax(0,1fr)_130px_150px_100px]
                        gap-4 border-b border-slate-100
                        bg-slate-50/60 px-6 py-3
                        text-[10px] font-bold uppercase
                        tracking-[0.14em] text-slate-400
                        lg:grid
                    "
                >
                    <span>Task</span>
                    <span>Priority</span>
                    <span>Status</span>
                    <span class="text-right">Actions</span>
                </div>


                <div class="divide-y divide-slate-100">

                    @foreach ($tasks as $task)

                        <div
                            class="
                                group relative grid gap-4
                                px-5 py-5 transition duration-200
                                sm:px-6
                                lg:grid-cols-[minmax(0,1fr)_130px_150px_100px]
                                lg:items-center

                                {{ $createdTaskId == $task->id
                                    ? 'bg-blue-50/60'
                                    : 'hover:bg-slate-50/70'
                                }}
                            "
                        >

                            @if ($createdTaskId == $task->id)

                                <div
                                    class="
                                        absolute bottom-0 left-0 top-0
                                        w-1 rounded-r-full bg-blue-500
                                    "
                                ></div>

                            @endif



                            {{-- =================================================
                                TASK INFO
                            ================================================== --}}
                            <div class="flex min-w-0 gap-4">

                                {{-- Icon --}}
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



                                <div class="min-w-0">

                                    {{-- Title --}}
                                    <div
                                        class="
                                            flex flex-wrap
                                            items-center gap-2
                                        "
                                    >

                                        <h2
                                            class="
                                                truncate text-sm
                                                font-bold text-slate-950
                                                sm:text-base
                                            "
                                        >
                                            {{ $task->title }}
                                        </h2>


                                        @if ($createdTaskId == $task->id)

                                            <span
                                                class="
                                                    rounded-lg
                                                    bg-blue-100 px-2 py-1
                                                    text-[10px] font-bold
                                                    uppercase tracking-wide
                                                    text-blue-700
                                                "
                                            >
                                                New
                                            </span>

                                        @endif

                                    </div>



                                    {{-- Description --}}
                                    @if ($task->description)

                                        <p
                                            class="
                                                mt-1.5 line-clamp-2
                                                max-w-2xl text-sm
                                                leading-6 text-slate-500
                                            "
                                        >
                                            {{ $task->description }}
                                        </p>

                                    @else

                                        <p
                                            class="
                                                mt-1.5 text-xs
                                                italic text-slate-400
                                            "
                                        >
                                            No description
                                        </p>

                                    @endif



                                    {{-- Date --}}
                                    <div
                                        class="
                                            mt-2.5 flex flex-wrap
                                            items-center gap-3
                                        "
                                    >

                                        @if ($task->due_date)

                                            <span
                                                class="
                                                    inline-flex items-center
                                                    gap-1.5 text-xs
                                                    font-medium text-slate-400
                                                "
                                            >
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
                                                class="
                                                    inline-flex items-center
                                                    gap-1.5 text-xs
                                                    font-medium text-slate-400
                                                "
                                            >
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



                            {{-- =================================================
                                PRIORITY
                            ================================================== --}}
                            <div>

                                <div
                                    class="
                                        mb-1 text-[10px]
                                        font-bold uppercase
                                        tracking-wide text-slate-400
                                        lg:hidden
                                    "
                                >
                                    Priority
                                </div>

                                <span
                                    class="
                                        inline-flex items-center gap-1.5
                                        rounded-lg px-2.5 py-1.5
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



                            {{-- =================================================
                                STATUS
                            ================================================== --}}
                            <div>

                                <div
                                    class="
                                        mb-1 text-[10px]
                                        font-bold uppercase
                                        tracking-wide text-slate-400
                                        lg:hidden
                                    "
                                >
                                    Status
                                </div>

                                <span
                                    class="
                                        inline-flex rounded-lg
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



                            {{-- =================================================
                                ACTIONS
                            ================================================== --}}
                            <div
                                class="
                                    flex items-center gap-2
                                    lg:justify-end
                                "
                            >

                                {{-- Edit --}}
                                <a
                                    href="{{ route('tasks.edit', $task) }}"
                                    class="
                                        inline-flex h-9 w-9
                                        items-center justify-center
                                        rounded-lg border
                                        border-slate-200 bg-white
                                        text-slate-500
                                        transition
                                        hover:border-blue-200
                                        hover:bg-blue-50
                                        hover:text-blue-600
                                    "
                                    aria-label="Edit {{ $task->title }}"
                                    title="Edit task"
                                >
                                    <x-icon
                                        name="edit"
                                        class="h-4 w-4"
                                    />
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
                                            border-rose-100
                                            bg-white text-rose-500
                                            transition
                                            hover:border-rose-200
                                            hover:bg-rose-50
                                            hover:text-rose-600
                                        "
                                        aria-label="Delete {{ $task->title }}"
                                        title="Delete task"
                                    >
                                        <x-icon
                                            name="trash"
                                            class="h-4 w-4"
                                        />
                                    </button>

                                </form>

                            </div>

                        </div>

                    @endforeach

                </div>



                {{-- Footer --}}
                <div
                    class="
                        flex flex-col gap-3
                        border-t border-slate-100
                        bg-slate-50/50 px-5 py-4
                        sm:flex-row sm:items-center
                        sm:justify-between sm:px-6
                    "
                >

                    <p
                        class="
                            text-xs font-medium text-slate-400
                        "
                    >
                        Showing
                        <span class="font-bold text-slate-600">
                            {{ $tasks->count() }}
                        </span>
                        {{ Str::plural('task', $tasks->count()) }}
                    </p>


                    <button
                        type="button"
                        data-open-task-modal
                        class="
                            inline-flex items-center gap-1.5
                            text-xs font-bold text-blue-600
                            transition hover:text-blue-800
                        "
                    >
                        <x-icon name="plus" class="h-3.5 w-3.5" />

                        Add another task
                    </button>

                </div>

            @endif

        </section>

    </div>



    {{-- =============================================================
        ADD TASK MODAL
    ============================================================== --}}
    <div
        id="task-modal"
        class="
            {{ $errors->any() ? 'flex' : 'hidden' }}
            fixed inset-0 z-50
            items-center justify-center
            overflow-y-auto
            bg-slate-950/50
            px-4 py-6
            backdrop-blur-sm
        "
        data-has-errors="{{ $errors->any() ? 'true' : 'false' }}"
        role="dialog"
        aria-modal="true"
        aria-labelledby="task-modal-title"
    >

        <div
            class="
                my-auto w-full max-w-xl
                overflow-hidden rounded-2xl
                border border-slate-200
                bg-white shadow-2xl
                shadow-slate-950/20
            "
        >

            {{-- Modal Header --}}
            <div
                class="
                    flex items-start justify-between
                    gap-4 border-b border-slate-100
                    px-6 py-5
                "
            >

                <div class="flex items-start gap-3">

                    <span
                        class="
                            flex h-10 w-10 shrink-0
                            items-center justify-center
                            rounded-xl bg-blue-50 text-blue-600
                        "
                    >
                        <x-icon
                            name="clipboard"
                            class="h-5 w-5"
                        />
                    </span>


                    <div>

                        <h2
                            id="task-modal-title"
                            class="
                                text-lg font-bold
                                text-slate-950
                                sm:text-xl
                            "
                        >
                            Add New Task
                        </h2>


                        <p
                            class="
                                mt-1 text-sm
                                leading-5 text-slate-500
                            "
                        >
                            Add your task details, priority, status and deadline.
                        </p>

                    </div>

                </div>


                <button
                    type="button"
                    data-close-task-modal
                    class="
                        flex h-9 w-9 shrink-0
                        items-center justify-center
                        rounded-lg text-slate-400
                        transition
                        hover:bg-slate-100
                        hover:text-slate-700
                    "
                    aria-label="Close form"
                >
                    <x-icon name="x" class="h-5 w-5" />
                </button>

            </div>



            {{-- Modal Form --}}
            <form
                method="POST"
                action="{{ route('tasks.store') }}"
                data-task-form
                autocomplete="off"
            >

                @csrf


                <div class="px-6 py-6">

                    @include('tasks._form')

                </div>


                {{-- Modal Footer --}}
                <div
                    class="
                        flex flex-col-reverse gap-3
                        border-t border-slate-100
                        bg-slate-50/60
                        px-6 py-4
                        sm:flex-row sm:justify-end
                    "
                >

                    <button
                        type="button"
                        data-close-task-modal
                        class="
                            inline-flex h-11
                            items-center justify-center
                            rounded-xl
                            border border-slate-200
                            bg-white px-5
                            text-sm font-semibold
                            text-slate-600
                            shadow-sm transition
                            hover:bg-slate-50
                            hover:text-slate-900
                        "
                    >
                        Cancel
                    </button>


                    <button
                        type="submit"
                        class="
                            inline-flex h-11
                            items-center justify-center
                            gap-2 rounded-xl
                            bg-blue-950 px-5
                            text-sm font-semibold
                            text-white
                            shadow-md shadow-blue-950/10
                            transition
                            hover:bg-blue-900
                        "
                    >
                        <x-icon
                            name="check"
                            class="h-4 w-4"
                        />

                        Create Task
                    </button>

                </div>

            </form>

        </div>

    </div>

@endsection
