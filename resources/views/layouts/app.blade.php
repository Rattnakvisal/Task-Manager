<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Task Manager')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-[#f6f8fc] text-slate-900 antialiased">

    @php
        $navItems = [
            [
                'label' => 'Dashboard',
                'route' => 'dashboard',
                'icon' => 'grid',
                'active' => request()->routeIs('dashboard') || request()->routeIs('home'),
            ],
            [
                'label' => 'My Tasks',
                'route' => 'tasks.index',
                'icon' => 'list',
                'active' => request()->routeIs('tasks.*'),
            ],
            [
                'label' => 'Calendar',
                'route' => 'calendar',
                'icon' => 'calendar',
                'active' => request()->routeIs('calendar'),
            ],
            [
                'label' => 'Priority',
                'route' => 'priority',
                'icon' => 'flag',
                'active' => request()->routeIs('priority'),
            ],
            [
                'label' => 'All Tasks',
                'route' => 'all-tasks',
                'icon' => 'list',
                'active' => request()->routeIs('all-tasks'),
            ],
        ];

        $notificationTasks = auth()->user()->tasks()->whereNotNull('end_date')
            ->where('status', '!=', 'completed')
            ->where(function ($query) {
                $query->whereDate('end_date', now()->toDateString())
                    ->orWhereDate('end_date', now()->addDay()->toDateString());
            })
            ->orderBy('end_date')
            ->orderByDesc('created_at')
            ->get();
    @endphp


    <div class="app-shell min-h-screen lg:grid lg:grid-cols-[260px_minmax(0,1fr)]" data-app-shell>

        {{-- =========================================================
        DESKTOP SIDEBAR
    ========================================================== --}}
        <aside id="app-sidebar"
            class="fixed inset-y-0 left-0 z-50 hidden w-[260px] flex-col
               border-r border-slate-200/80 bg-white lg:flex">

            {{-- Logo --}}
            <div class="flex h-[72px] items-center justify-between border-b border-slate-100 px-5">

                <a href="{{ route('dashboard') }}" class="group flex items-center gap-3">

                    <span
                        class="relative flex h-10 w-10 items-center justify-center
                           rounded-xl bg-gradient-to-br from-blue-950 to-blue-700
                           text-white shadow-md shadow-blue-950/15">

                        <x-icon name="task-logo" class="h-5 w-5" />

                        <span
                            class="absolute -right-0.5 -top-0.5 h-2.5 w-2.5
                               rounded-full border-2 border-white bg-blue-400">
                        </span>
                    </span>

                    <div class="sidebar-label">
                        <p class="text-[15px] font-bold tracking-tight text-slate-950">
                            Task Manager
                        </p>

                        <p class="mt-0.5 text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">
                            Workspace
                        </p>
                    </div>

                </a>

                <button type="button" data-sidebar-toggle class="sidebar-toggle hidden h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 lg:flex" aria-label="Collapse sidebar" aria-expanded="true">
                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6" /></svg>
                </button>

            </div>


            {{-- Navigation --}}
            <div class="flex-1 overflow-y-auto px-4 py-6">

                <p class="sidebar-label mb-3 px-3 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">
                    Main Menu
                </p>

                <nav class="space-y-1.5">

                    @foreach ($navItems as $item)
                        <a href="{{ route($item['route']) }}" title="{{ $item['label'] }}"
                            class="
                            group relative flex h-11 items-center gap-3 rounded-xl px-3
                            text-sm font-semibold transition-all duration-200

                            {{ $item['active'] ? 'bg-blue-50 text-blue-700' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}
                       ">

                            {{-- Active indicator --}}
                            @if ($item['active'])
                                <span class="absolute -left-4 h-6 w-1 rounded-r-full bg-blue-600">
                                </span>
                            @endif


                            {{-- Icon --}}
                            <span
                                class="
                                flex h-8 w-8 shrink-0 items-center justify-center rounded-lg
                                transition

                                {{ $item['active']
                                    ? 'bg-white text-blue-600 shadow-sm'
                                    : 'text-slate-400 group-hover:bg-white group-hover:text-slate-700 group-hover:shadow-sm' }}
                            ">

                                <x-icon :name="$item['icon']" class="h-[17px] w-[17px]" />

                            </span>


                            <span class="sidebar-label flex-1">
                                {{ $item['label'] }}
                            </span>


                            @if ($item['active'])
                                <span class="sidebar-label h-1.5 w-1.5 rounded-full bg-blue-600"></span>
                            @endif

                        </a>
                    @endforeach

                </nav>

            </div>


            {{-- Productivity Card --}}
            <div class="sidebar-extra px-4 pb-4">

                <div
                    class="relative overflow-hidden rounded-2xl
                       bg-gradient-to-br from-blue-950 via-blue-900 to-indigo-900
                       p-4 text-white shadow-lg shadow-blue-950/10">

                    {{-- Decoration --}}
                    <div
                        class="pointer-events-none absolute -right-10 -top-10
                           h-28 w-28 rounded-full bg-blue-400/20 blur-2xl">
                    </div>

                    <div
                        class="pointer-events-none absolute -bottom-12 -left-8
                           h-24 w-24 rounded-full bg-indigo-400/20 blur-2xl">
                    </div>


                    <div class="relative">

                        <span
                            class="mb-3 flex h-9 w-9 items-center justify-center
                               rounded-lg bg-white/10 text-blue-100 ring-1 ring-white/10">

                            <x-icon name="layers" class="h-4 w-4" />

                        </span>


                        <p class="text-sm font-bold">
                            Stay organized
                        </p>

                        <p class="mt-1.5 text-xs leading-5 text-blue-100/70">
                            Plan your work, set priorities and stay focused.
                        </p>


                        <a href="{{ route('tasks.index') }}#new-task" data-open-task-modal
                            class="mt-4 inline-flex items-center gap-1.5 text-xs font-bold text-white">

                            Create task

                            <span aria-hidden="true">
                                →
                            </span>

                        </a>

                    </div>

                </div>

            </div>


            {{-- Sidebar Footer --}}
            <div class="border-t border-slate-100 px-4 py-4">

                <div class="flex items-center gap-3 rounded-xl px-2 py-2">

                    <span
                        class="flex h-9 w-9 shrink-0 items-center justify-center
                           rounded-full bg-blue-100 text-xs font-bold text-blue-700">
                        TM
                    </span>


                    <div class="sidebar-label min-w-0 flex-1">

                        <p class="truncate text-xs font-bold text-slate-800">
                            {{ auth()->user()->name }}
                        </p>

                        <p class="truncate text-[11px] text-slate-400">
                            {{ auth()->user()->email }}
                        </p>

                    </div>

                    <form method="POST" action="{{ route('logout') }}" class="sidebar-extra">
                        @csrf
                        <button type="submit" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" title="Sign out" aria-label="Sign out">
                            <x-icon name="logout" class="h-4 w-4" />
                        </button>
                    </form>

                </div>

            </div>

        </aside>



        {{-- =========================================================
        MAIN APPLICATION AREA
    ========================================================== --}}
        <div class="min-w-0 lg:col-start-2">

            {{-- =====================================================
            TOP HEADER
        ====================================================== --}}
            <header
                class="sticky top-0 z-40 border-b border-slate-200/80
                   bg-white/90 backdrop-blur-xl">

                <div
                    class="mx-auto flex h-[72px] max-w-[1600px]
                       items-center justify-between px-4 sm:px-6 lg:px-8">


                    {{-- Mobile Logo --}}
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 lg:hidden">

                        <span
                            class="flex h-9 w-9 items-center justify-center
                               rounded-xl bg-blue-950 text-white shadow-sm">

                            <x-icon name="task-logo" class="h-4.5 w-4.5" />

                        </span>

                        <span class="text-sm font-bold text-slate-950">
                            Task Manager
                        </span>

                    </a>


                    {{-- Desktop breadcrumb/heading --}}
                    <div class="hidden items-center gap-2 lg:flex">

                        <span class="text-sm font-medium text-slate-400">
                            Workspace
                        </span>

                        <span class="text-slate-300">
                            /
                        </span>

                        <span class="text-sm font-semibold text-slate-700">
                            @if (request()->routeIs('dashboard') || request()->routeIs('home'))
                                Dashboard
                            @elseif(request()->routeIs('tasks.*'))
                                My Tasks
                            @elseif(request()->routeIs('calendar'))
                                Calendar
                            @elseif(request()->routeIs('priority'))
                                Priority
                            @elseif(request()->routeIs('completed'))
                                Completed
                            @else
                                Tasks
                            @endif
                        </span>

                    </div>


                    {{-- Actions --}}
                    <div class="flex items-center gap-2 sm:gap-3">

                        {{-- Search --}}
                        <form method="GET" action="{{ route('all-tasks') }}" class="relative hidden md:block" data-global-search-form>
                            <label class="relative block">
                                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                <input
                                    type="search"
                                    name="q"
                                    value="{{ request('q') }}"
                                    data-global-search
                                    placeholder="Search tasks..."
                                    class="h-10 w-56 rounded-xl border border-slate-200 bg-white pl-9 pr-10 text-sm text-slate-700 shadow-sm outline-none transition placeholder:text-slate-400 hover:bg-slate-50 focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                                >
                                <kbd class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 rounded-md border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-[10px] font-medium text-slate-400">
                                    /
                                </kbd>
                            </label>

                            <div
                                data-search-results
                                class="absolute right-0 top-12 z-50 hidden w-96 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/10"
                            >
                                <div class="px-4 py-3 text-sm text-slate-500">
                                    Type to search tasks.
                                </div>
                            </div>
                        </form>

                        {{-- Divider --}}
                        <div class="hidden h-7 w-px bg-slate-200 sm:block"></div>
                        {{-- Theme --}}
                        <button type="button" data-theme-toggle
                            class="relative flex h-10 w-10 items-center justify-center rounded-xl border border-transparent text-slate-500 transition hover:border-slate-200 hover:bg-white hover:text-slate-700 hover:shadow-sm"
                            aria-label="Switch to dark mode" aria-pressed="false">
                            <svg data-theme-sun viewBox="0 0 24 24" class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.66 6.34l1.41-1.41"/></svg>
                            <svg data-theme-moon viewBox="0 0 24 24" class="hidden h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"/></svg>
                        </button>
                        {{-- Notifications --}}
                        <div class="relative">
                            <button type="button"
                                data-toggle-notifications
                                class="relative flex h-10 w-10 items-center justify-center rounded-xl border border-transparent text-slate-500 transition hover:border-slate-200 hover:bg-white hover:text-slate-700 hover:shadow-sm"
                                aria-label="Notifications">

                                <x-icon name="bell" class="h-[18px] w-[18px]" />

                                @if ($notificationTasks->isNotEmpty())
                                    <span class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full border-2 border-white bg-blue-600 px-1 text-[10px] font-bold text-white">
                                        {{ $notificationTasks->count() }}
                                    </span>
                                @endif

                            </button>

                            <div data-notifications-panel class="absolute right-0 top-12 z-50 hidden w-80 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/10">
                                <div class="border-b border-slate-100 px-4 py-3">
                                    <p class="text-sm font-bold text-slate-950">Notifications</p>
                                    <p class="mt-0.5 text-xs text-slate-400">Tasks ending today or tomorrow.</p>
                                </div>

                                <div class="max-h-80 overflow-y-auto">
                                    @forelse ($notificationTasks as $task)
                                        @php
                                            $endsToday = $task->end_date->isToday();
                                        @endphp
                                        <a href="{{ route('all-tasks', ['q' => $task->title]) }}" class="flex gap-3 border-b border-slate-100 px-4 py-3 transition hover:bg-slate-50 last:border-b-0">
                                            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $endsToday ? 'bg-rose-50 text-rose-600' : 'bg-amber-50 text-amber-600' }}">
                                                <x-icon name="bell" class="h-4 w-4" />
                                            </span>
                                            <span class="min-w-0">
                                                <span class="block truncate text-sm font-bold text-slate-950">{{ $task->title }}</span>
                                                <span class="mt-1 block text-xs leading-5 text-slate-500">
                                                    {{ $endsToday ? 'End date is today' : 'End date is tomorrow' }}
                                                    - {{ $task->end_date->format('M d, Y') }}
                                                </span>
                                            </span>
                                        </a>
                                    @empty
                                        <div class="px-4 py-8 text-center">
                                            <span class="mx-auto flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                                                <x-icon name="check-circle" class="h-5 w-5" />
                                            </span>
                                            <p class="mt-3 text-sm font-bold text-slate-900">No alerts</p>
                                            <p class="mt-1 text-xs text-slate-400">No tasks end today or tomorrow.</p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>

                </div>


                {{-- =====================================================
                MOBILE NAVIGATION
            ====================================================== --}}
                <div class="border-t border-slate-100 bg-white px-4 py-2 lg:hidden">

                    <nav
                        class="flex gap-2 overflow-x-auto pb-1
                           [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">

                        @foreach ($navItems as $item)
                            <a href="{{ route($item['route']) }}"
                                class="
                                inline-flex h-9 shrink-0 items-center gap-2
                                rounded-lg px-3 text-xs font-semibold transition

                                {{ $item['active'] ? 'bg-blue-950 text-white' : 'bg-slate-50 text-slate-500 hover:bg-slate-100' }}
                           ">

                                <x-icon :name="$item['icon']" class="h-3.5 w-3.5" />

                                {{ $item['label'] }}

                            </a>
                        @endforeach

                    </nav>

                </div>

            </header>



            {{-- =========================================================
            MAIN CONTENT
        ========================================================== --}}
            <main data-page-content
                class="mx-auto min-h-[calc(100vh-72px)] max-w-[1600px]
                   px-4 py-6 sm:px-6 sm:py-8 lg:px-8">

                {{-- =====================================================
                SUCCESS MESSAGE
            ====================================================== --}}
                @if (session('success'))
                    <div id="success-alert"
                        class="
                        mb-6 flex items-start justify-between gap-4
                        rounded-2xl border border-emerald-200
                        bg-emerald-50/90 px-4 py-3.5
                        text-sm text-emerald-700 shadow-sm
                    ">

                        <div class="flex items-start gap-3">

                            <span
                                class="mt-0.5 flex h-8 w-8 shrink-0
                                   items-center justify-center rounded-lg
                                   bg-emerald-100 text-emerald-600">

                                <x-icon name="check-circle" class="h-4 w-4" />

                            </span>


                            <div>

                                <p class="font-semibold text-emerald-800">
                                    Success
                                </p>

                                <p class="mt-0.5 text-xs leading-5 text-emerald-700/80">
                                    {{ session('success') }}
                                </p>

                            </div>

                        </div>


                        <button type="button" data-dismiss-alert
                            class="
                            flex h-8 w-8 shrink-0 items-center justify-center
                            rounded-lg text-emerald-600
                            transition hover:bg-emerald-100
                        "
                            aria-label="Dismiss message">

                            <x-icon name="x" class="h-4 w-4" />

                        </button>

                    </div>
                @endif

                @if ($notificationTasks->isNotEmpty())
                    <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-800 shadow-sm">
                        <div class="flex items-start gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-amber-600 shadow-sm">
                                <x-icon name="bell" class="h-4 w-4" />
                            </span>
                            <div>
                                <p class="font-bold">End date alert</p>
                                <p class="mt-1 text-xs leading-5 text-amber-700/80">
                                    {{ $notificationTasks->count() }} task{{ $notificationTasks->count() === 1 ? '' : 's' }}
                                    {{ $notificationTasks->count() === 1 ? 'ends' : 'end' }} today or tomorrow.
                                    Open the notification bell for details.
                                </p>
                            </div>
                        </div>
                    </div>
                @endif


                {{-- =====================================================
                VALIDATION ERRORS
            ====================================================== --}}
                @if ($errors->any())

                    <div
                        class="mb-6 rounded-2xl border border-rose-200
                           bg-rose-50 px-4 py-4 text-sm text-rose-700">

                        <div class="flex items-start gap-3">

                            <span
                                class="flex h-8 w-8 shrink-0 items-center justify-center
                                   rounded-lg bg-rose-100 text-rose-600">

                                <x-icon name="x" class="h-4 w-4" />

                            </span>


                            <div>

                                <p class="font-bold text-rose-800">
                                    Please check the following
                                </p>

                                <ul class="mt-2 list-disc space-y-1 pl-4 text-xs">

                                    @foreach ($errors->all() as $error)
                                        <li>
                                            {{ $error }}
                                        </li>
                                    @endforeach

                                </ul>

                            </div>

                        </div>

                    </div>

                @endif


                {{-- Blade Component Content --}}
                {{ $slot ?? '' }}


                {{-- Blade View Content --}}
                @yield('content')

            </main>

        </div>

    </div>


    {{-- =============================================================
    SMALL UI SCRIPTS
============================================================== --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // Dismiss alerts
            document.querySelectorAll('[data-dismiss-alert]').forEach(function(button) {
                button.addEventListener('click', function() {
                    const alert = button.closest('#success-alert');

                    if (alert) {
                        alert.remove();
                    }
                });
            });

        });
    </script>

    <div
        id="edit-task-modal"
        class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-slate-950/50 px-4 py-6 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        aria-labelledby="edit-task-modal-title"
    >
        <div class="my-auto w-full max-w-xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/20">
            <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                        <x-icon name="edit" class="h-5 w-5" />
                    </span>
                    <div>
                        <h2 id="edit-task-modal-title" class="text-lg font-bold text-slate-950 sm:text-xl">
                            Edit Task
                        </h2>
                        <p class="mt-1 text-sm leading-5 text-slate-500">
                            Update task details, priority, status and deadline.
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    data-close-edit-modal
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                    aria-label="Close edit form"
                >
                    <x-icon name="x" class="h-5 w-5" />
                </button>
            </div>

            <form method="POST" action="" data-edit-task-form autocomplete="off">
                @csrf
                @method('PUT')

                <div class="space-y-5 px-6 py-6">
                    <div>
                        <label for="edit_title" class="mb-2 flex items-center gap-2 text-sm font-semibold text-slate-700">
                            <span class="flex h-6 w-6 items-center justify-center rounded-md bg-blue-50 text-blue-600">
                                <x-icon name="clipboard" class="h-3.5 w-3.5" />
                            </span>
                            Task Title
                            <span class="text-rose-500">*</span>
                        </label>
                        <input
                            id="edit_title"
                            type="text"
                            name="title"
                            placeholder="What needs to be done?"
                            autocomplete="off"
                            required
                            class="h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-900 outline-none transition-all duration-200 placeholder:font-normal placeholder:text-slate-400 hover:border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                        >
                    </div>

                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <label for="edit_description" class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                <span class="flex h-6 w-6 items-center justify-center rounded-md bg-violet-50 text-violet-600">
                                    <x-icon name="list" class="h-3.5 w-3.5" />
                                </span>
                                Description
                            </label>
                            <span class="text-[11px] font-medium text-slate-400">Optional</span>
                        </div>
                        <textarea
                            id="edit_description"
                            name="description"
                            rows="4"
                            placeholder="Add notes, details, or useful information about this task..."
                            autocomplete="off"
                            class="min-h-[120px] w-full resize-none rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm leading-6 text-slate-900 outline-none transition-all duration-200 placeholder:text-slate-400 hover:border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                        ></textarea>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="edit_priority" class="mb-2 flex items-center gap-2 text-sm font-semibold text-slate-700">
                                <span class="flex h-6 w-6 items-center justify-center rounded-md bg-amber-50 text-amber-600">
                                    <x-icon name="flag" class="h-3.5 w-3.5" />
                                </span>
                                Priority
                            </label>
                            <select
                                id="edit_priority"
                                name="priority"
                                class="h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-800 outline-none transition-all duration-200 hover:border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                            >
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>

                        <div>
                            <label for="edit_status" class="mb-2 flex items-center gap-2 text-sm font-semibold text-slate-700">
                                <span class="flex h-6 w-6 items-center justify-center rounded-md bg-blue-50 text-blue-600">
                                    <x-icon name="check-circle" class="h-3.5 w-3.5" />
                                </span>
                                Status
                            </label>
                            <select
                                id="edit_status"
                                name="status"
                                class="h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-800 outline-none transition-all duration-200 hover:border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                            >
                                <option value="pending">Pending</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <label for="edit_due_date" class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                <span class="flex h-6 w-6 items-center justify-center rounded-md bg-emerald-50 text-emerald-600">
                                    <x-icon name="calendar" class="h-3.5 w-3.5" />
                                </span>
                                Due Date
                            </label>
                            <span class="text-[11px] font-medium text-slate-400">Optional</span>
                        </div>
                        <input
                            id="edit_due_date"
                            type="date"
                            name="due_date"
                            class="h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-800 outline-none transition-all duration-200 hover:border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                        >
                    </div>

                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <label for="edit_end_date" class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                <span class="flex h-6 w-6 items-center justify-center rounded-md bg-cyan-50 text-cyan-600">
                                    <x-icon name="calendar" class="h-3.5 w-3.5" />
                                </span>
                                End Date
                            </label>
                            <span class="text-[11px] font-medium text-slate-400">Optional</span>
                        </div>
                        <input
                            id="edit_end_date"
                            type="date"
                            name="end_date"
                            class="h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-800 outline-none transition-all duration-200 hover:border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                        >
                    </div>
                    </div>

                    <div class="rounded-xl border border-blue-100 bg-blue-50/60 p-3.5">
                        <div class="flex items-start gap-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white text-blue-600 shadow-sm">
                                <x-icon name="edit" class="h-4 w-4" />
                            </span>
                            <div>
                                <p class="text-xs font-bold text-blue-900">Edit task details</p>
                                <p class="mt-1 text-xs leading-5 text-blue-700/70">
                                    Save changes to update this task in the database.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 bg-slate-50/60 px-6 py-4 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        data-close-edit-modal
                        class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-5 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50 hover:text-slate-900"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-blue-950 px-5 text-sm font-semibold text-white shadow-md shadow-blue-950/10 transition hover:bg-blue-900"
                    >
                        <x-icon name="check" class="h-4 w-4" />
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

</body>

</html>
