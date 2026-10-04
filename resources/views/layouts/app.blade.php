<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Task Manager')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="task-manager-ui min-h-screen bg-[#f6f8fc] text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100 transition-colors duration-200 {{ request()->routeIs('dashboard', 'home') ? 'dashboard-page' : '' }}">

    @php
        $navItems = [
            [
                'label' => 'Dashboard',
                'key' => 'dashboard',
                'route' => 'dashboard',
                'icon' => 'grid',
                'active' => request()->routeIs('dashboard') || request()->routeIs('home'),
            ],
            [
                'label' => 'My Tasks',
                'key' => 'my_tasks',
                'route' => 'tasks.index',
                'icon' => 'kanban',
                'active' => request()->routeIs('tasks.index', 'tasks.create', 'tasks.show', 'tasks.edit', 'all-tasks'),
            ],
            [
                'label' => 'Projects',
                'key' => 'projects',
                'route' => 'projects',
                'icon' => 'project',
                'active' => request()->routeIs('projects'),
            ],
            [
                'label' => 'Calendar',
                'key' => 'calendar',
                'route' => 'calendar',
                'icon' => 'calendar',
                'active' => request()->routeIs('calendar'),
            ],
            [
                'label' => 'Priority',
                'key' => 'priority',
                'route' => 'priority',
                'icon' => 'flag',
                'active' => request()->routeIs('priority'),
            ],
            [
                'label' => 'Analytics',
                'key' => 'analytics',
                'route' => 'analytics',
                'icon' => 'analytics',
                'active' => request()->routeIs('analytics'),
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
               border-r border-slate-200/80 bg-white lg:flex dark:border-slate-800 dark:bg-slate-900 transition-colors duration-200">

            {{-- Logo --}}
            <div class="sidebar-brand flex h-[72px] items-center justify-between border-b border-slate-100 dark:border-slate-800 px-5">

                <a href="{{ route('dashboard') }}" class="group flex items-center gap-3">

                    <span
                        class="relative flex h-10 w-10 items-center justify-center
                           rounded-xl bg-gradient-to-br from-blue-950 to-blue-700
                           text-white shadow-md shadow-blue-950/15">

                        <x-icon name="task-logo" class="h-5 w-5" />

                        <span
                            class="absolute -right-0.5 -top-0.5 h-2.5 w-2.5
                               rounded-full border-2 border-white dark:border-slate-900 bg-blue-400">
                        </span>
                    </span>

                    <div class="sidebar-label">
                        <p class="text-[15px] font-bold tracking-tight text-slate-950 dark:text-white">
                            Task Manager
                        </p>

                        <p class="mt-0.5 text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400 dark:text-slate-500">
                            Workspace
                        </p>
                    </div>

                </a>

                <button type="button" data-sidebar-toggle class="sidebar-toggle hidden h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200 lg:flex" aria-label="Collapse sidebar" aria-expanded="true">
                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6" /></svg>
                </button>

            </div>


            {{-- Navigation --}}
            <div class="sidebar-navigation flex-1 overflow-y-auto px-4 py-6">

                <nav class="space-y-1.5">

                    @foreach ($navItems as $item)
                        <a href="{{ route($item['route']) }}" title="{{ $item['label'] }}"
                            class="
                            group relative flex h-11 items-center gap-3 rounded-xl px-3
                            text-sm font-semibold transition-all duration-200

                            {{ $item['active'] ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/70 dark:text-blue-300' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/60 dark:hover:text-slate-100' }}
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
                                    ? 'bg-white text-blue-600 shadow-sm dark:bg-slate-800 dark:text-blue-400'
                                    : 'text-slate-400 group-hover:bg-white group-hover:text-slate-700 group-hover:shadow-sm dark:group-hover:bg-slate-800 dark:group-hover:text-slate-200' }}
                            ">

                                <x-icon :name="$item['icon']" class="h-[17px] w-[17px]" />

                            </span>


                            <span class="sidebar-label flex-1" data-i18n="{{ $item['key'] ?? strtolower(str_replace(' ', '_', $item['label'])) }}">
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
            <div class="sidebar-productivity sidebar-extra px-4 pb-4">

                <div
                    class="relative overflow-hidden rounded-2xl
                       bg-gradient-to-br from-blue-950 via-blue-900 to-indigo-900 dark:from-slate-900 dark:via-blue-950 dark:to-slate-900 border border-transparent dark:border-slate-800
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


                        <button type="button"
                            data-open-task-modal
                            class="mt-4 inline-flex items-center gap-1.5 text-xs font-bold text-white transition hover:underline">

                            <span data-i18n="create_task">Create task</span>

                            <span aria-hidden="true">
                                →
                            </span>

                        </button>

                    </div>

                </div>

            </div>


            {{-- Sidebar Footer --}}
            <div class="border-t border-slate-100 dark:border-slate-800 px-4 py-4">

                <div class="flex items-center gap-3 rounded-xl px-2 py-2">

                    <span
                        class="flex h-9 w-9 shrink-0 items-center justify-center
                           rounded-full bg-blue-100 text-xs font-bold text-blue-700 dark:bg-blue-950 dark:text-blue-300">
                        TM
                    </span>


                    <div class="sidebar-label min-w-0 flex-1">

                        <p class="truncate text-xs font-bold text-slate-800 dark:text-slate-200">
                            {{ auth()->user()->name }}
                        </p>

                        <p class="truncate text-[11px] text-slate-400 dark:text-slate-500">
                            {{ auth()->user()->email }}
                        </p>

                    </div>

                    <form method="POST" action="{{ route('logout') }}" class="sidebar-extra" data-confirm-logout>
                        @csrf
                        <button type="submit" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200" title="Sign out" aria-label="Sign out">
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
                   bg-white/90 backdrop-blur-xl dark:border-slate-800 dark:bg-slate-900/90 transition-colors duration-200">

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

                        <span class="text-sm font-bold text-slate-950 dark:text-white">
                            Task Manager
                        </span>

                    </a>


                    {{-- Desktop breadcrumb/heading --}}
                    <div class="hidden items-center gap-2 lg:flex">

                        <span class="text-sm font-medium text-slate-400 dark:text-slate-500">
                            Workspace
                        </span>

                        <span class="text-slate-300 dark:text-slate-700">
                            /
                        </span>

                        <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                            @if (request()->routeIs('dashboard') || request()->routeIs('home'))
                                Dashboard
                            @elseif(request()->routeIs('tasks.today'))
                                Today
                            @elseif(request()->routeIs('tasks.overdue'))
                                Overdue
                            @elseif(request()->routeIs('tasks.completed'))
                                Completed
                            @elseif(request()->routeIs('tasks.create'))
                                Create Task
                            @elseif(request()->routeIs('tasks.show'))
                                Task Details
                            @elseif(request()->routeIs('tasks.edit'))
                                Edit Task
                            @elseif(request()->routeIs('tasks.index'))
                                My Tasks
                            @elseif(request()->routeIs('calendar'))
                                Calendar
                            @elseif(request()->routeIs('priority'))
                                Priority
                            @elseif(request()->routeIs('all-tasks'))
                                All Tasks
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
                                    class="h-10 w-56 rounded-xl border border-slate-200 bg-white pl-9 pr-10 text-sm text-slate-700 shadow-sm outline-none transition placeholder:text-slate-400 hover:bg-slate-50 focus:border-blue-500 focus:ring-4 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:placeholder:text-slate-500 dark:hover:bg-slate-750 dark:focus:ring-blue-900/40"
                                >
                                <kbd class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 rounded-md border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-[10px] font-medium text-slate-400 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-500">
                                    /
                                </kbd>
                            </label>

                            <div
                                data-search-results
                                class="absolute right-0 top-12 z-50 hidden w-96 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/10 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                            >
                                <div class="px-4 py-3 text-sm text-slate-500 dark:text-slate-400">
                                    Type to search tasks.
                                </div>
                            </div>
                        </form>

                        {{-- New Task Button (Modal Trigger) --}}
                        <button type="button" data-open-task-modal
                            class="flex h-10 items-center gap-1.5 rounded-xl bg-blue-600 px-3.5 text-xs font-bold text-white shadow-sm shadow-blue-500/20 transition hover:bg-blue-700 active:scale-95"
                            title="Create New Task">
                            <x-icon name="plus" class="h-3.5 w-3.5" />
                            <span class="hidden sm:inline" data-i18n="new_task">New Task</span>
                        </button>

                        {{-- Divider --}}
                        <div class="hidden h-7 w-px bg-slate-200 dark:bg-slate-800 sm:block"></div>

                        {{-- Command Palette Trigger --}}
                        <button type="button" data-open-command-palette
                            class="relative hidden sm:flex h-10 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-slate-600 dark:hover:bg-slate-700/70 dark:hover:text-white"
                            title="Command Palette (Ctrl + K)">
                            <x-icon name="command" class="h-3.5 w-3.5 text-blue-600 dark:text-blue-400" />
                            <span class="hidden md:inline" data-i18n="commands">Commands</span>
                            <kbd class="rounded border border-slate-200 bg-slate-100 px-1 py-0.5 text-[10px] text-slate-500 font-mono dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400">⌘K</kbd>
                        </button>

                        {{-- Language Switcher --}}
                        <button type="button" data-language-toggle
                            class="relative flex h-10 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 text-xs font-bold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:border-slate-600 dark:hover:bg-slate-700/70"
                            title="Switch Language / ប្តូរភាសា" aria-label="Toggle language">
                            <x-icon name="globe" class="h-4 w-4" />
                            <span data-lang-text class="font-bold text-blue-700 dark:text-blue-400">EN</span>
                        </button>

                        {{-- Theme --}}
                        <button type="button" data-theme-toggle
                            class="relative flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200/80 bg-white text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-slate-600 dark:hover:bg-slate-700/70 dark:hover:text-white"
                            aria-label="Switch to dark mode" aria-pressed="false" title="Toggle Theme">
                            <svg data-theme-sun viewBox="0 0 24 24" class="h-4.5 w-4.5 text-amber-500 transition" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.66 6.34l1.41-1.41"/></svg>
                            <svg data-theme-moon viewBox="0 0 24 24" class="hidden h-4.5 w-4.5 text-blue-400 transition" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"/></svg>
                        </button>

                        {{-- Notifications --}}
                        <div class="relative">
                            <button type="button"
                                data-toggle-notifications
                                class="relative flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200/80 bg-white text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-slate-600 dark:hover:bg-slate-700/70 dark:hover:text-white"
                                aria-label="Notifications">

                                <x-icon name="bell" class="h-[18px] w-[18px]" />

                                @if ($notificationTasks->isNotEmpty())
                                    <span class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full border-2 border-white dark:border-slate-900 bg-blue-600 px-1 text-[10px] font-bold text-white">
                                        {{ $notificationTasks->count() }}
                                    </span>
                                @endif

                            </button>

                            <div data-notifications-panel class="absolute right-0 top-12 z-50 hidden w-80 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/10 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                <div class="border-b border-slate-100 dark:border-slate-700 px-4 py-3">
                                    <p class="text-sm font-bold text-slate-950 dark:text-white">Notifications</p>
                                    <p class="mt-0.5 text-xs text-slate-400 dark:text-slate-400">Tasks ending today or tomorrow.</p>
                                </div>

                                <div class="max-h-80 overflow-y-auto">
                                    @forelse ($notificationTasks as $task)
                                        @php
                                            $endsToday = $task->end_date->isToday();
                                        @endphp
                                        <a href="{{ route('all-tasks', ['q' => $task->title]) }}" class="flex gap-3 border-b border-slate-100 dark:border-slate-700 px-4 py-3 transition hover:bg-slate-50 dark:hover:bg-slate-700/50 last:border-b-0">
                                            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $endsToday ? 'bg-rose-50 text-rose-600 dark:bg-rose-950 dark:text-rose-400' : 'bg-amber-50 text-amber-600 dark:bg-amber-950 dark:text-amber-400' }}">
                                                <x-icon name="bell" class="h-4 w-4" />
                                            </span>
                                            <span class="min-w-0">
                                                <span class="block truncate text-sm font-bold text-slate-950 dark:text-white">{{ $task->title }}</span>
                                                <span class="mt-1 block text-xs leading-5 text-slate-500 dark:text-slate-400">
                                                    {{ $endsToday ? 'End date is today' : 'End date is tomorrow' }}
                                                    - {{ $task->end_date->format('M d, Y') }}
                                                </span>
                                            </span>
                                        </a>
                                    @empty
                                        <div class="px-4 py-8 text-center">
                                            <span class="mx-auto flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400">
                                                <x-icon name="check-circle" class="h-5 w-5" />
                                            </span>
                                            <p class="mt-3 text-sm font-bold text-slate-900 dark:text-white">No alerts</p>
                                            <p class="mt-1 text-xs text-slate-400">No tasks end today or tomorrow.</p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                        <button type="button" class="header-avatar" data-open-workspace-profile aria-label="Open your workspace profile">TM</button>
                    </div>

                </div>


                {{-- =====================================================
                MOBILE NAVIGATION
            ====================================================== --}}
                <div class="border-t border-slate-100 bg-white px-4 py-2 lg:hidden dark:border-slate-800 dark:bg-slate-900">

                    <nav
                        class="flex gap-2 overflow-x-auto pb-1
                           [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">

                        @foreach ($navItems as $item)
                            <a href="{{ route($item['route']) }}"
                                class="
                                inline-flex h-9 shrink-0 items-center gap-2
                                rounded-lg px-3 text-xs font-semibold transition

                                {{ $item['active'] ? 'bg-blue-950 text-white dark:bg-blue-600' : 'bg-slate-50 text-slate-500 hover:bg-slate-100 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' }}
                           ">

                                <x-icon :name="$item['icon']" class="h-3.5 w-3.5" />

                                <span data-i18n="{{ $item['key'] ?? strtolower(str_replace(' ', '_', $item['label'])) }}">{{ $item['label'] }}</span>

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
                FLASH MESSAGES FOR SWEETALERT & PAGE
            ====================================================== --}}
                @if (session('success'))
                    <div id="flash-session-success" data-message="{{ session('success') }}" class="hidden"></div>
                @endif
                @if (session('error'))
                    <div id="flash-session-error" data-message="{{ session('error') }}" class="hidden"></div>
                @endif
                @if ($errors->any())
                    <div id="flash-session-errors" data-errors="{{ json_encode($errors->all()) }}" class="hidden"></div>
                @endif

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
        CREATE TASK MODAL (AVAILABLE GLOBALLY ON ALL PAGES)
    ============================================================== --}}
    <div
        id="task-modal"
        class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-slate-950/50 px-4 py-6 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        aria-labelledby="task-modal-title"
    >
        <div class="my-auto w-full max-w-xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/20 dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5 dark:border-slate-800">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400">
                        <x-icon name="plus" class="h-5 w-5" />
                    </span>
                    <div>
                        <h2 id="task-modal-title" class="text-lg font-bold text-slate-950 sm:text-xl dark:text-white" data-i18n="create_task">
                            Create New Task
                        </h2>
                        <p class="mt-1 text-sm leading-5 text-slate-500 dark:text-slate-400" data-i18n="create_task_sub">
                            Add a new task with checklist, priority, and deadline.
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    data-close-task-modal
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200"
                    aria-label="Close"
                >
                    <x-icon name="x" class="h-5 w-5" />
                </button>
            </div>

            <form method="POST" action="{{ route('tasks.store') }}" data-task-form novalidate autocomplete="off">
                @csrf

                <div class="max-h-[75vh] space-y-5 overflow-y-auto px-6 py-6">
                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <label for="create_title" class="flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-300">
                                <span class="flex h-6 w-6 items-center justify-center rounded-md bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400">
                                    <x-icon name="clipboard" class="h-3.5 w-3.5" />
                                </span>
                                <span data-i18n="task_title">Task Title</span>
                                <span class="text-rose-500">*</span>
                            </label>
                            <button
                                type="button"
                                id="create-ai-suggest-btn"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-purple-200/90 bg-purple-50/90 px-2.5 py-1 text-[11px] font-semibold text-purple-700 shadow-2xs transition hover:bg-purple-100 hover:border-purple-300 dark:border-purple-800 dark:bg-purple-950/40 dark:text-purple-300"
                                title="Auto-detect category & priority"
                            >
                                <x-icon name="sparkles" class="h-3 w-3 text-purple-600 dark:text-purple-400" />
                                <span data-i18n="ai_suggest">✨ AI Suggest</span>
                            </button>
                        </div>
                        <input
                            id="create_title"
                            type="text"
                            name="title"
                            placeholder="What needs to be done?"
                            autocomplete="off"
                            required
                            class="h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-900 outline-none transition-all duration-200 placeholder:text-slate-400 hover:border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:focus:ring-blue-900/40"
                        >
                    </div>

                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <label for="create_description" class="flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-300">
                                <span class="flex h-6 w-6 items-center justify-center rounded-md bg-violet-50 text-violet-600 dark:bg-violet-950/50 dark:text-violet-400">
                                    <x-icon name="list" class="h-3.5 w-3.5" />
                                </span>
                                <span data-i18n="description">Description</span>
                            </label>
                            <span class="text-[11px] font-medium text-slate-400" data-i18n="optional">Optional</span>
                        </div>
                        <textarea
                            id="create_description"
                            name="description"
                            rows="3"
                            placeholder="Add notes, details, or useful information..."
                            autocomplete="off"
                            class="min-h-[90px] w-full resize-none rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm leading-6 text-slate-900 outline-none transition-all duration-200 placeholder:text-slate-400 hover:border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:focus:ring-blue-900/40"
                        ></textarea>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="create_category" class="mb-2 flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-300">
                                <span class="flex h-6 w-6 items-center justify-center rounded-md bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400">
                                    <x-icon name="layers" class="h-3.5 w-3.5" />
                                </span>
                                <span data-i18n="category">Category</span>
                            </label>
                            <select
                                id="create_category"
                                name="category"
                                class="h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-800 outline-none transition-all duration-200 hover:border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:focus:ring-blue-900/40"
                            >
                                <option value="" data-i18n="no_category">-- None --</option>
                                <option value="Work">💼 Work (ការងារ)</option>
                                <option value="Personal">🏠 Personal (ផ្ទាល់ខ្លួន)</option>
                                <option value="Urgent">⚡ Urgent (បន្ទាន់)</option>
                                <option value="Design">🎨 Design (រចនា)</option>
                                <option value="Dev">💻 Dev (អភិវឌ្ឍន៍)</option>
                                <option value="Study">📚 Study (ការសិក្សា)</option>
                                <option value="Finance">💵 Finance (ហិរញ្ញវត្ថុ)</option>
                            </select>
                        </div>

                        <div>
                            <label for="create_priority" class="mb-2 flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-300">
                                <span class="flex h-6 w-6 items-center justify-center rounded-md bg-amber-50 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400">
                                    <x-icon name="flag" class="h-3.5 w-3.5" />
                                </span>
                                <span data-i18n="priority">Priority</span>
                            </label>
                            <select
                                id="create_priority"
                                name="priority"
                                class="h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-800 outline-none transition-all duration-200 hover:border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:focus:ring-blue-900/40"
                            >
                                <option value="low" data-i18n="low">🟢 Low (ទាប)</option>
                                <option value="medium" selected data-i18n="medium">🟡 Medium (មធ្យម)</option>
                                <option value="high" data-i18n="high">🔴 High (ខ្ពស់)</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="create_status" class="mb-2 flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-300">
                                <span class="flex h-6 w-6 items-center justify-center rounded-md bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400">
                                    <x-icon name="check-circle" class="h-3.5 w-3.5" />
                                </span>
                                <span data-i18n="status">Status</span>
                            </label>
                            <select
                                id="create_status"
                                name="status"
                                class="h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-800 outline-none transition-all duration-200 hover:border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:focus:ring-blue-900/40"
                            >
                                <option value="pending" selected data-i18n="pending">Pending (មិនទាន់ធ្វើ)</option>
                                <option value="in_progress" data-i18n="in_progress">In Progress (កំពុងដំណើរការ)</option>
                                <option value="completed" data-i18n="completed">Completed (បានបញ្ចប់)</option>
                            </select>
                        </div>

                        <div>
                            <div class="mb-2 flex items-center justify-between">
                                <label for="create_due_date" class="flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-300">
                                    <span class="flex h-6 w-6 items-center justify-center rounded-md bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
                                        <x-icon name="calendar" class="h-3.5 w-3.5" />
                                    </span>
                                    <span data-i18n="due_date">Due Date</span>
                                </label>
                                <span class="text-[11px] font-medium text-slate-400" data-i18n="optional">Optional</span>
                            </div>
                            <input
                                id="create_due_date"
                                type="date"
                                name="due_date"
                                class="h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-800 outline-none transition-all duration-200 hover:border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:focus:ring-blue-900/40"
                            >
                        </div>
                    </div>

                    {{-- Checklist / Subtasks --}}
                    <div class="rounded-xl border border-slate-200/80 bg-slate-50/70 p-4 dark:border-slate-700 dark:bg-slate-800/50">
                        <div class="mb-2 flex items-center justify-between">
                            <label class="flex items-center gap-2 text-sm font-bold text-slate-800 dark:text-slate-200">
                                <span class="flex h-6 w-6 items-center justify-center rounded-md bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
                                    <x-icon name="check" class="h-3.5 w-3.5" />
                                </span>
                                <span data-i18n="checklist">Checklist / Subtasks (កិច្ចការរង)</span>
                            </label>
                            <button
                                type="button"
                                id="create-ai-breakdown-btn"
                                class="ai-btn-magic inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-[11px] font-semibold text-white shadow-xs"
                                title="Generate structured subtasks with AI"
                            >
                                <x-icon name="sparkles" class="h-3 w-3" />
                                <span data-i18n="ai_magic_breakdown">✨ AI Magic Breakdown</span>
                            </button>
                        </div>

                        <div id="create-subtask-list" class="space-y-2 mb-3">
                            {{-- Dynamically inserted items --}}
                        </div>

                        <div class="flex gap-2">
                            <input
                                type="text"
                                id="create-new-subtask-input"
                                placeholder="Add a checklist item / បន្ថែមកិច្ចការរង..."
                                class="h-10 flex-1 rounded-lg border border-slate-200 bg-white px-3 text-xs text-slate-800 outline-none placeholder:text-slate-400 focus:border-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                            >
                            <button
                                type="button"
                                id="create-add-subtask-btn"
                                class="inline-flex h-10 items-center justify-center gap-1 rounded-lg bg-blue-600 px-3 text-xs font-bold text-white transition hover:bg-blue-700"
                            >
                                <x-icon name="plus" class="h-3.5 w-3.5" />
                                <span data-i18n="add">Add</span>
                            </button>
                        </div>
                        <input type="hidden" name="subtasks" id="create-subtasks-payload" value="[]">
                    </div>

                    {{-- Pin to Top --}}
                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-white p-3.5 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:hover:bg-slate-800/80">
                        <input type="checkbox" name="is_pinned" value="1" class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <div class="flex items-center gap-2">
                            <x-icon name="pin" class="h-4 w-4 text-amber-500" />
                            <span class="text-sm font-semibold text-slate-800 dark:text-slate-200" data-i18n="pin_to_top">Pin to top of list (📌 ខ្ទាស់នៅខាងលើបង្អស់)</span>
                        </div>
                    </label>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 bg-slate-50/60 px-6 py-4 sm:flex-row sm:justify-end dark:border-slate-800 dark:bg-slate-900/60">
                    <button
                        type="button"
                        data-close-task-modal
                        class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-5 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                        data-i18n="cancel"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-blue-950 px-5 text-sm font-semibold text-white shadow-md shadow-blue-950/10 transition hover:bg-blue-900 dark:bg-blue-600 dark:hover:bg-blue-500"
                    >
                        <x-icon name="plus" class="h-4 w-4" />
                        <span data-i18n="create_task">Create Task</span>
                    </button>
                </div>
            </form>
        </div>
    </div>


    {{-- =============================================================
        EDIT TASK MODAL
    ============================================================== --}}
    <div
        id="edit-task-modal"
        class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-slate-950/50 px-4 py-6 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        aria-labelledby="edit-task-modal-title"
    >
        <div class="my-auto w-full max-w-xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/20 dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5 dark:border-slate-800">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400">
                        <x-icon name="edit" class="h-5 w-5" />
                    </span>
                    <div>
                        <h2 id="edit-task-modal-title" class="text-lg font-bold text-slate-950 sm:text-xl dark:text-white" data-i18n="edit_task">
                            Edit Task
                        </h2>
                        <p class="mt-1 text-sm leading-5 text-slate-500 dark:text-slate-400" data-i18n="edit_task_sub">
                            Update task details, checklist, priority, and status.
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    data-close-edit-modal
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200"
                    aria-label="Close"
                >
                    <x-icon name="x" class="h-5 w-5" />
                </button>
            </div>

            <form method="POST" action="" data-edit-task-form novalidate autocomplete="off">
                @csrf
                @method('PUT')

                <div class="max-h-[75vh] space-y-5 overflow-y-auto px-6 py-6">
                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <label for="edit_title" class="flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-300">
                                <span class="flex h-6 w-6 items-center justify-center rounded-md bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400">
                                    <x-icon name="clipboard" class="h-3.5 w-3.5" />
                                </span>
                                <span data-i18n="task_title">Task Title</span>
                                <span class="text-rose-500">*</span>
                            </label>
                            <button
                                type="button"
                                id="edit-ai-suggest-btn"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-purple-200/90 bg-purple-50/90 px-2.5 py-1 text-[11px] font-semibold text-purple-700 shadow-2xs transition hover:bg-purple-100 hover:border-purple-300 dark:border-purple-800 dark:bg-purple-950/40 dark:text-purple-300"
                                title="Auto-detect category & priority"
                            >
                                <x-icon name="sparkles" class="h-3 w-3 text-purple-600 dark:text-purple-400" />
                                <span data-i18n="ai_suggest">✨ AI Suggest</span>
                            </button>
                        </div>
                        <input
                            id="edit_title"
                            type="text"
                            name="title"
                            placeholder="What needs to be done?"
                            autocomplete="off"
                            required
                            class="h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-900 outline-none transition-all duration-200 placeholder:text-slate-400 hover:border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:focus:ring-blue-900/40"
                        >
                    </div>

                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <label for="edit_description" class="flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-300">
                                <span class="flex h-6 w-6 items-center justify-center rounded-md bg-violet-50 text-violet-600 dark:bg-violet-950/50 dark:text-violet-400">
                                    <x-icon name="list" class="h-3.5 w-3.5" />
                                </span>
                                <span data-i18n="description">Description</span>
                            </label>
                            <span class="text-[11px] font-medium text-slate-400" data-i18n="optional">Optional</span>
                        </div>
                        <textarea
                            id="edit_description"
                            name="description"
                            rows="3"
                            placeholder="Add notes, details, or useful information..."
                            autocomplete="off"
                            class="min-h-[90px] w-full resize-none rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm leading-6 text-slate-900 outline-none transition-all duration-200 placeholder:text-slate-400 hover:border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:focus:ring-blue-900/40"
                        ></textarea>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="edit_category" class="mb-2 flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-300">
                                <span class="flex h-6 w-6 items-center justify-center rounded-md bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400">
                                    <x-icon name="layers" class="h-3.5 w-3.5" />
                                </span>
                                <span data-i18n="category">Category</span>
                            </label>
                            <select
                                id="edit_category"
                                name="category"
                                class="h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-800 outline-none transition-all duration-200 hover:border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:focus:ring-blue-900/40"
                            >
                                <option value="" data-i18n="no_category">-- None --</option>
                                <option value="Work">💼 Work (ការងារ)</option>
                                <option value="Personal">🏠 Personal (ផ្ទាល់ខ្លួន)</option>
                                <option value="Urgent">⚡ Urgent (បន្ទាន់)</option>
                                <option value="Design">🎨 Design (រចនា)</option>
                                <option value="Dev">💻 Dev (អភិវឌ្ឍន៍)</option>
                                <option value="Study">📚 Study (ការសិក្សា)</option>
                                <option value="Finance">💵 Finance (ហិរញ្ញវត្ថុ)</option>
                            </select>
                        </div>

                        <div>
                            <label for="edit_priority" class="mb-2 flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-300">
                                <span class="flex h-6 w-6 items-center justify-center rounded-md bg-amber-50 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400">
                                    <x-icon name="flag" class="h-3.5 w-3.5" />
                                </span>
                                <span data-i18n="priority">Priority</span>
                            </label>
                            <select
                                id="edit_priority"
                                name="priority"
                                class="h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-800 outline-none transition-all duration-200 hover:border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:focus:ring-blue-900/40"
                            >
                                <option value="low" data-i18n="low">🟢 Low (ទាប)</option>
                                <option value="medium" data-i18n="medium">🟡 Medium (មធ្យម)</option>
                                <option value="high" data-i18n="high">🔴 High (ខ្ពស់)</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="edit_status" class="mb-2 flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-300">
                                <span class="flex h-6 w-6 items-center justify-center rounded-md bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400">
                                    <x-icon name="check-circle" class="h-3.5 w-3.5" />
                                </span>
                                <span data-i18n="status">Status</span>
                            </label>
                            <select
                                id="edit_status"
                                name="status"
                                class="h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-800 outline-none transition-all duration-200 hover:border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:focus:ring-blue-900/40"
                            >
                                <option value="pending" data-i18n="pending">Pending (មិនទាន់ធ្វើ)</option>
                                <option value="in_progress" data-i18n="in_progress">In Progress (កំពុងដំណើរការ)</option>
                                <option value="completed" data-i18n="completed">Completed (បានបញ្ចប់)</option>
                            </select>
                        </div>

                        <div>
                            <div class="mb-2 flex items-center justify-between">
                                <label for="edit_due_date" class="flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-300">
                                    <span class="flex h-6 w-6 items-center justify-center rounded-md bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
                                        <x-icon name="calendar" class="h-3.5 w-3.5" />
                                    </span>
                                    <span data-i18n="due_date">Due Date</span>
                                </label>
                                <span class="text-[11px] font-medium text-slate-400" data-i18n="optional">Optional</span>
                            </div>
                            <input
                                id="edit_due_date"
                                type="date"
                                name="due_date"
                                class="h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-800 outline-none transition-all duration-200 hover:border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:focus:ring-blue-900/40"
                            >
                        </div>
                    </div>

                    {{-- Checklist / Subtasks --}}
                    <div class="rounded-xl border border-slate-200/80 bg-slate-50/70 p-4 dark:border-slate-700 dark:bg-slate-800/50">
                        <div class="mb-2 flex items-center justify-between">
                            <label class="flex items-center gap-2 text-sm font-bold text-slate-800 dark:text-slate-200">
                                <span class="flex h-6 w-6 items-center justify-center rounded-md bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
                                    <x-icon name="check" class="h-3.5 w-3.5" />
                                </span>
                                <span data-i18n="checklist">Checklist / Subtasks (កិច្ចការរង)</span>
                            </label>
                            <button
                                type="button"
                                id="edit-ai-breakdown-btn"
                                class="ai-btn-magic inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-[11px] font-semibold text-white shadow-xs"
                                title="Generate structured subtasks with AI"
                            >
                                <x-icon name="sparkles" class="h-3 w-3" />
                                <span data-i18n="ai_magic_breakdown">✨ AI Magic Breakdown</span>
                            </button>
                        </div>

                        <div id="edit-subtask-list" class="space-y-2 mb-3">
                            {{-- Dynamically populated --}}
                        </div>

                        <div class="flex gap-2">
                            <input
                                type="text"
                                id="edit-new-subtask-input"
                                placeholder="Add a checklist item / បន្ថែមកិច្ចការរង..."
                                class="h-10 flex-1 rounded-lg border border-slate-200 bg-white px-3 text-xs text-slate-800 outline-none placeholder:text-slate-400 focus:border-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                            >
                            <button
                                type="button"
                                id="edit-add-subtask-btn"
                                class="inline-flex h-10 items-center justify-center gap-1 rounded-lg bg-blue-600 px-3 text-xs font-bold text-white transition hover:bg-blue-700"
                            >
                                <x-icon name="plus" class="h-3.5 w-3.5" />
                                <span data-i18n="add">Add</span>
                            </button>
                        </div>
                        <input type="hidden" name="subtasks" id="edit-subtasks-payload" value="[]">
                    </div>

                    {{-- Pin to Top --}}
                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-white p-3.5 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:hover:bg-slate-800/80">
                        <input type="checkbox" id="edit_is_pinned" name="is_pinned" value="1" class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <div class="flex items-center gap-2">
                            <x-icon name="pin" class="h-4 w-4 text-amber-500" />
                            <span class="text-sm font-semibold text-slate-800 dark:text-slate-200" data-i18n="pin_to_top">Pin to top of list (📌 ខ្ទាស់នៅខាងលើបង្អស់)</span>
                        </div>
                    </label>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 bg-slate-50/60 px-6 py-4 sm:flex-row sm:justify-end dark:border-slate-800 dark:bg-slate-900/60">
                    <button
                        type="button"
                        data-close-edit-modal
                        class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-5 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                        data-i18n="cancel"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-blue-950 px-5 text-sm font-semibold text-white shadow-md shadow-blue-950/10 transition hover:bg-blue-900 dark:bg-blue-600 dark:hover:bg-blue-500"
                    >
                        <x-icon name="check" class="h-4 w-4" />
                        <span data-i18n="save_changes">Save Changes</span>
                    </button>
                </div>
            </form>
        </div>
    </div>


    {{-- =============================================================
        COMMAND PALETTE (CTRL+K / CMD+K)
    ============================================================== --}}
    <div
        id="command-palette-modal"
        class="fixed inset-0 z-50 hidden items-start justify-center overflow-y-auto bg-slate-950/50 p-4 pt-20 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
    >
        <div class="w-full max-w-xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center gap-3 border-b border-slate-100 px-4 py-3 dark:border-slate-800">
                <x-icon name="search" class="h-5 w-5 text-slate-400" />
                <input
                    type="text"
                    id="cmd-palette-input"
                    placeholder="Type a command or search tasks... / វាយបញ្ជា ឬស្វែងរក..."
                    class="h-10 flex-1 bg-transparent text-sm text-slate-900 outline-none placeholder:text-slate-400 dark:text-white"
                    autocomplete="off"
                >
                <kbd class="rounded border border-slate-200 bg-slate-100 px-1.5 py-0.5 text-[10px] font-mono text-slate-500 dark:border-slate-700 dark:bg-slate-800">ESC</kbd>
            </div>

            <div class="max-h-96 overflow-y-auto p-2" id="cmd-palette-list">
                {{-- Quick Navigation & Actions --}}
                <div class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400" data-i18n="quick_actions">
                    Quick Actions / សកម្មភាពរហ័ស
                </div>

                <button type="button" class="cmd-item flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-slate-800 transition dark:text-slate-200" data-cmd="new-task">
                    <span class="flex items-center gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400">
                            <x-icon name="plus" class="h-4 w-4" />
                        </span>
                        <span data-i18n="create_task">Create New Task</span>
                    </span>
                    <kbd class="rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-[10px] font-mono text-slate-400 dark:border-slate-700 dark:bg-slate-800">N</kbd>
                </button>

                <button type="button" class="cmd-item flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-slate-800 transition dark:text-slate-200" data-cmd="open-ai-chat">
                    <span class="flex items-center gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400">
                            <x-icon name="sparkles" class="h-4 w-4" />
                        </span>
                        <span data-i18n="ai_chatbot">AI Copilot Chatbot (ជជែកជាមួយ AI)</span>
                    </span>
                    <kbd class="rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-[10px] font-mono text-slate-400 dark:border-slate-700 dark:bg-slate-800">Shift + A</kbd>
                </button>

                <button type="button" class="cmd-item flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-slate-800 transition dark:text-slate-200" data-cmd="ai-standup">
                    <span class="flex items-center gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-950/60 dark:text-purple-400">
                            <x-icon name="activity" class="h-4 w-4" />
                        </span>
                        <span data-i18n="ai_briefing_title">AI Daily Standup Briefing (សង្ខេប AI)</span>
                    </span>
                    <kbd class="rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-[10px] font-mono text-slate-400 dark:border-slate-700 dark:bg-slate-800">B</kbd>
                </button>

                <a href="{{ route('dashboard') }}" class="cmd-item flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-slate-800 transition dark:text-slate-200">
                    <span class="flex items-center gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                            <x-icon name="grid" class="h-4 w-4" />
                        </span>
                        <span data-i18n="dashboard">Dashboard</span>
                    </span>
                    <kbd class="rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-[10px] font-mono text-slate-400 dark:border-slate-700 dark:bg-slate-800">G D</kbd>
                </a>

                <a href="{{ route('tasks.index') }}" class="cmd-item flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-slate-800 transition dark:text-slate-200">
                    <span class="flex items-center gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400">
                            <x-icon name="list" class="h-4 w-4" />
                        </span>
                        <span data-i18n="my_tasks">My Tasks</span>
                    </span>
                    <kbd class="rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-[10px] font-mono text-slate-400 dark:border-slate-700 dark:bg-slate-800">G T</kbd>
                </a>

                <a href="{{ route('projects') }}" class="cmd-item flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-slate-800 transition dark:text-slate-200">
                    <span class="flex items-center gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400">
                            <x-icon name="project" class="h-4 w-4" />
                        </span>
                        <span data-i18n="projects">Projects & Categories</span>
                    </span>
                    <kbd class="rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-[10px] font-mono text-slate-400 dark:border-slate-700 dark:bg-slate-800">G R</kbd>
                </a>

                <a href="{{ route('calendar') }}" class="cmd-item flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-slate-800 transition dark:text-slate-200">
                    <span class="flex items-center gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                            <x-icon name="calendar" class="h-4 w-4" />
                        </span>
                        <span data-i18n="calendar">Calendar</span>
                    </span>
                    <kbd class="rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-[10px] font-mono text-slate-400 dark:border-slate-700 dark:bg-slate-800">G C</kbd>
                </a>

                <a href="{{ route('priority') }}" class="cmd-item flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-slate-800 transition dark:text-slate-200">
                    <span class="flex items-center gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400">
                            <x-icon name="flag" class="h-4 w-4" />
                        </span>
                        <span data-i18n="priority">Priority</span>
                    </span>
                    <kbd class="rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-[10px] font-mono text-slate-400 dark:border-slate-700 dark:bg-slate-800">G P</kbd>
                </a>

                <a href="{{ route('analytics') }}" class="cmd-item flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-slate-800 transition dark:text-slate-200">
                    <span class="flex items-center gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                            <x-icon name="analytics" class="h-4 w-4" />
                        </span>
                        <span data-i18n="analytics">Analytics & Insights</span>
                    </span>
                    <kbd class="rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-[10px] font-mono text-slate-400 dark:border-slate-700 dark:bg-slate-800">G A</kbd>
                </a>

                <a href="{{ route('tasks.today') }}" class="cmd-item flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-slate-800 transition dark:text-slate-200">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400"><x-icon name="clock" class="h-4 w-4" /></span>
                    Today's Tasks
                </a>

                <a href="{{ route('tasks.overdue') }}" class="cmd-item flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-slate-800 transition dark:text-slate-200">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-50 text-rose-600 dark:bg-rose-950 dark:text-rose-400"><x-icon name="clock" class="h-4 w-4" /></span>
                    Overdue Tasks
                </a>

                <a href="{{ route('tasks.completed') }}" class="cmd-item flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-slate-800 transition dark:text-slate-200">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400"><x-icon name="check-circle" class="h-4 w-4" /></span>
                    Completed Tasks
                </a>

                <div class="mt-2 border-t border-slate-100 pt-2 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:border-slate-800" data-i18n="tools_preferences">
                    Tools & Preferences / ឧបករណ៍ & ចំណូលចិត្ត
                </div>

                <a href="{{ route('tasks.export', 'csv') }}" class="cmd-item flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-slate-800 transition dark:text-slate-200">
                    <span class="flex items-center gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-50 text-violet-600 dark:bg-violet-950/60 dark:text-violet-400">
                            <x-icon name="download" class="h-4 w-4" />
                        </span>
                        <span data-i18n="export_csv">Export Tasks as CSV (ទាញយក CSV)</span>
                    </span>
                </a>

                <a href="{{ route('tasks.export', 'json') }}" class="cmd-item flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-slate-800 transition dark:text-slate-200">
                    <span class="flex items-center gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-cyan-50 text-cyan-600 dark:bg-cyan-950/60 dark:text-cyan-400">
                            <x-icon name="download" class="h-4 w-4" />
                        </span>
                        <span data-i18n="export_json">Export Tasks as JSON (ទាញយក JSON)</span>
                    </span>
                </a>

                <button type="button" class="cmd-item flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-slate-800 transition dark:text-slate-200" data-cmd="toggle-lang">
                    <span class="flex items-center gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400">
                            <x-icon name="globe" class="h-4 w-4" />
                        </span>
                        <span data-i18n="switch_lang">Switch Language (ភាសាខ្មែរ / English)</span>
                    </span>
                    <kbd class="rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-[10px] font-mono text-slate-400 dark:border-slate-700 dark:bg-slate-800">L</kbd>
                </button>

                <button type="button" class="cmd-item flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-slate-800 transition dark:text-slate-200" data-cmd="toggle-theme">
                    <span class="flex items-center gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400">
                            <x-icon name="activity" class="h-4 w-4" />
                        </span>
                        <span data-i18n="toggle_dark">Toggle Dark / Light Mode</span>
                    </span>
                    <kbd class="rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-[10px] font-mono text-slate-400 dark:border-slate-700 dark:bg-slate-800">T</kbd>
                </button>
            </div>
        </div>
    </div>

    {{-- =============================================================
        AI TASK COPILOT CHATBOT (DRAWER & FLOATING LAUNCHER)
    ============================================================== --}}

    {{-- 1. Floating Launcher Button (FAB) --}}
    <div id="ai-chatbot-launcher" class="fixed bottom-6 right-6 z-40">
        <button
            type="button"
            id="ai-chatbot-launcher-btn"
            class="gemini-fab-btn group relative flex h-14 w-14 items-center justify-center rounded-2xl bg-white dark:bg-slate-900 text-slate-800 dark:text-white shadow-xl shadow-slate-900/10 border border-slate-200/90 dark:border-slate-800 transition-all duration-300 hover:scale-110 hover:shadow-2xl hover:border-indigo-400 dark:hover:border-indigo-500 focus:outline-none"
            aria-label="Open Gemini AI"
            title="Open Gemini AI (Shift + A)"
        >
            {{-- Online status dot --}}
            <span class="absolute -top-1 -right-1 flex h-3.5 w-3.5 z-20">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex h-3.5 w-3.5 rounded-full border-2 border-white dark:border-slate-900 bg-emerald-500"></span>
            </span>

            {{-- NIU Lottie Animation Logo Container --}}
            <div class="pointer-events-none relative z-10 flex h-11 w-11 items-center justify-center overflow-hidden rounded-xl p-0.5 group-hover:scale-110 transition-transform duration-300" data-lottie="niu"></div>
        </button>
    </div>

    {{-- 2. Clean & Minimalist Chatbot Drawer --}}
    <div
        id="ai-chatbot-drawer"
        class="ai-chat-drawer fixed bottom-6 right-6 z-50 hidden flex-col overflow-hidden rounded-3xl border border-slate-200/90 bg-white shadow-2xl ring-1 ring-slate-900/5 sm:w-[420px] w-[calc(100vw-2rem)] h-[620px] max-h-[calc(100vh-3rem)] dark:border-slate-800 dark:bg-slate-900 dark:ring-white/10 dark:shadow-slate-950/70"
        role="dialog"
        aria-labelledby="ai-chat-title"
    >
        {{-- Drawer Header (Clean & Simple) --}}
        <div class="relative flex items-center justify-between border-b border-slate-100 bg-white px-4 py-3.5 text-slate-800 shadow-2xs dark:border-slate-800 dark:bg-slate-900 dark:text-white">
            <div class="flex items-center gap-3">
                {{-- Bot Avatar with animated niu.json logo --}}
                <div class="relative flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-100 dark:border-indigo-900/50 overflow-hidden p-0.5 shadow-2xs">
                    <div class="h-8 w-8 flex items-center justify-center pointer-events-none" data-lottie="niu"></div>
                </div>
                <div>
                    <h3 id="ai-chat-title" class="text-sm font-bold text-slate-900 dark:text-white leading-tight">Gemini</h3>
                    <div class="flex items-center gap-1.5 mt-0.5">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                        <span class="text-[11px] text-slate-500 dark:text-slate-400 font-medium" data-i18n="ai_chatbot_status">Online Assistant</span>
                    </div>
                </div>
            </div>

            {{-- Header Actions --}}
            <div class="flex items-center gap-1">
                {{-- Clear chat history button --}}
                <button
                    type="button"
                    id="ai-chat-clear-btn"
                    class="flex h-8 w-8 items-center justify-center rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 dark:hover:text-rose-400 transition"
                    title="Clear Chat History"
                    aria-label="Clear chat history"
                >
                    <x-icon name="trash" class="h-3.5 w-3.5" />
                </button>

                {{-- Close / Minimize button --}}
                <button
                    type="button"
                    id="ai-chat-close-btn"
                    class="flex h-8 w-8 items-center justify-center rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 dark:hover:text-slate-200 transition"
                    title="Close"
                    aria-label="Close chat"
                >
                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Messages Stream Container --}}
        <div id="ai-chat-messages" class="ai-chat-scrollbar flex-1 space-y-3.5 overflow-y-auto p-4 text-xs">
            {{-- Welcome Card (Clean, Simple & Friendly) --}}
            <div id="ai-chat-welcome" class="flex flex-col items-center justify-center text-center py-6 px-3">
                {{-- Centerpiece Animated niu.json Mascot --}}
                <div class="relative mb-3 flex h-20 w-20 items-center justify-center rounded-3xl bg-indigo-50/80 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-900/50 p-1 shadow-sm">
                    <div class="relative z-10 flex h-16 w-16 items-center justify-center overflow-hidden pointer-events-none" data-lottie="niu"></div>
                </div>

                <h4 class="text-base font-bold text-slate-900 dark:text-white" data-i18n="ai_welcome_title">
                    How can I help you today?
                </h4>
                <p class="mt-1 text-xs leading-relaxed text-slate-500 dark:text-slate-400 max-w-xs" data-i18n="ai_welcome_desc">
                    Ask me anything about your tasks, or try one of the suggestions below.
                </p>

                {{-- Clean Suggestion Pills --}}
                <div class="mt-5 flex flex-wrap justify-center gap-2 max-w-xs">
                    <button type="button" class="ai-suggestion-chip inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-2xs transition hover:border-indigo-400 hover:text-indigo-600 hover:scale-102 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-indigo-500 dark:hover:text-indigo-400" data-prompt="standup">
                        <span>☀️</span> Daily Standup
                    </button>
                    <button type="button" class="ai-suggestion-chip inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-2xs transition hover:border-indigo-400 hover:text-indigo-600 hover:scale-102 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-indigo-500 dark:hover:text-indigo-400" data-prompt="overdue">
                        <span>⚠️</span> Check Overdue
                    </button>
                    <button type="button" class="ai-suggestion-chip inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-2xs transition hover:border-indigo-400 hover:text-indigo-600 hover:scale-102 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-indigo-500 dark:hover:text-indigo-400" data-prompt="create task: ">
                        <span>➕</span> Create Task
                    </button>
                </div>
            </div>

            {{-- Dynamic Message Bubbles rendered by JavaScript --}}
            <div id="ai-chat-thread" class="space-y-3"></div>

            {{-- Typing Indicator with Animated niu.json Logo --}}
            <div id="ai-chat-typing" class="hidden items-center gap-2.5 pt-1 text-slate-400">
                <div class="relative flex h-7 w-7 shrink-0 items-center justify-center rounded-xl overflow-hidden bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200/60 dark:border-indigo-800/60 p-0.5" data-lottie="niu"></div>
                <div class="flex items-center gap-1.5 rounded-2xl rounded-tl-xs bg-slate-100 px-3.5 py-2 text-slate-600 dark:bg-slate-800 dark:text-slate-300 border border-slate-200/60 dark:border-slate-700/60">
                    <span class="text-[11px] font-semibold mr-1 text-indigo-600 dark:text-indigo-400" data-i18n="ai_thinking">Gemini is thinking</span>
                    <span class="typing-dot"></span>
                    <span class="typing-dot"></span>
                    <span class="typing-dot"></span>
                </div>
            </div>
        </div>

        {{-- Quick suggestion pills strip above input --}}
        <div class="ai-prompts-bar no-scrollbar flex items-center gap-1.5 overflow-x-auto border-t border-slate-100 bg-slate-50/70 px-3 py-2 dark:border-slate-800 dark:bg-slate-900/60">
            <button type="button" class="ai-quick-btn shrink-0 inline-flex items-center gap-1 rounded-full border border-slate-200/80 bg-white px-2.5 py-1 text-[11px] font-medium text-slate-600 transition hover:border-indigo-300 hover:text-indigo-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-indigo-500" data-prompt="standup">
                <span>☀️</span> Standup
            </button>
            <button type="button" class="ai-quick-btn shrink-0 inline-flex items-center gap-1 rounded-full border border-slate-200/80 bg-white px-2.5 py-1 text-[11px] font-medium text-slate-600 transition hover:border-indigo-300 hover:text-indigo-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-indigo-500" data-prompt="overdue">
                <span>⚠️</span> Overdue
            </button>
            <button type="button" class="ai-quick-btn shrink-0 inline-flex items-center gap-1 rounded-full border border-slate-200/80 bg-white px-2.5 py-1 text-[11px] font-medium text-slate-600 transition hover:border-indigo-300 hover:text-indigo-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-indigo-500" data-prompt="create task: ">
                <span>➕</span> New Task
            </button>
            <button type="button" class="ai-quick-btn shrink-0 inline-flex items-center gap-1 rounded-full border border-slate-200/80 bg-white px-2.5 py-1 text-[11px] font-medium text-slate-600 transition hover:border-indigo-300 hover:text-indigo-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-indigo-500" data-prompt="breakdown: ">
                <span>📋</span> Breakdown
            </button>
        </div>

        {{-- Input Footer Form --}}
        <div class="border-t border-slate-100 bg-white p-3 dark:border-slate-800 dark:bg-slate-900">
            <form id="ai-chat-form" class="flex items-center gap-2">
                <div class="relative flex-1 flex items-center">
                    <input
                        type="text"
                        id="ai-chat-input"
                        placeholder="Ask Gemini, or type 'create task: ...'"
                        class="h-10 w-full rounded-2xl border border-slate-200 bg-slate-50/80 pl-3.5 pr-8 text-xs text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white focus:ring-2 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:focus:ring-indigo-900/30"
                        autocomplete="off"
                    >
                    <span class="pointer-events-none absolute right-3 text-slate-400">
                        <kbd class="text-[9.5px] font-sans text-slate-400 border border-slate-200 rounded px-1 py-0.5 dark:border-slate-700">↵</kbd>
                    </span>
                </div>
                <button
                    type="submit"
                    id="ai-chat-send-btn"
                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-sm transition hover:bg-indigo-700 active:scale-95 disabled:opacity-50"
                    aria-label="Send message"
                >
                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                </button>
            </form>
            <div class="mt-2 flex items-center justify-between px-1 text-[10px] text-slate-400">
                <span>Shift+A to toggle</span>
                <span class="font-medium text-slate-500 dark:text-slate-400">Powered by Gemini</span>
            </div>
        </div>
    </div>

    <dialog id="workspace-profile" class="workspace-profile-dialog">
        <div class="panel-heading"><h2>Your Workspace</h2><button type="button" data-close-workspace-profile aria-label="Close workspace"><x-icon name="x" /></button></div>
        <p class="workspace-profile-description">Your tasks belong to your private workspace.</p>
        <div class="workspace-member"><span class="workspace-avatar">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(auth()->user()->name, 0, 1)) }}</span><div><strong>{{ auth()->user()->name }}</strong><p>{{ auth()->user()->email }}</p></div><span class="count-badge">Owner</span></div>
    </dialog>

    {{-- Confetti canvas for celebrations --}}
    <canvas id="confetti-canvas"></canvas>

</body>

</html>
