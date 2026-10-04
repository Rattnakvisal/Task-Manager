@extends('layouts.app')

@section('title', 'Dashboard - Task Manager')

@section('content')
    @php
        $total = $stats['Total Tasks']['value'];
        $completed = $stats['Completed']['value'];
        $inProgress = $stats['In Progress']['value'];
        $pending = $stats['Pending']['value'];
        $completedPercent = $total ? $completed / $total * 100 : 0;
        $activePercent = $total ? $inProgress / $total * 100 : 0;
        $calendarStart = $calendarMonth->copy()->startOfWeek(\Carbon\Carbon::SUNDAY);
        $calendarDays = (int) (ceil(($calendarMonth->dayOfWeek + $calendarMonth->daysInMonth) / 7) * 7);
        $tones = ['blue', 'amber', 'teal', 'purple'];
    @endphp

    <div class="reference-dashboard" data-dashboard>
        <div class="overview-grid">
            <section class="welcome-banner" aria-labelledby="welcome-heading">
                <img class="welcome-art" src="{{ asset('images/Task.png') }}" alt="Blue task clipboard with a small calendar and green plant" fetchpriority="high">
                <div class="welcome-copy">
                    <p class="welcome-greeting">Good morning,</p>
                    <h1 id="welcome-heading">{{ auth()->user()->name }} <span class="welcome-wave">👋</span></h1>
                    <p class="welcome-description">Stay focused on what matters most. Track deadlines, priority tasks,<br class="wide-break"> and make progress every day.</p>
                    <div class="welcome-actions">
                        <a href="{{ route('tasks.create') }}" data-open-task-modal class="dashboard-button primary"><x-icon name="plus" /> <span data-i18n="new_task">New Task</span></a>
                        <a class="dashboard-button secondary" href="{{ route('calendar') }}"><x-icon name="calendar" /> Open Calendar</a>
                    </div>
                </div>
            </section>

            <section class="dashboard-surface mini-calendar" aria-label="Task calendar" data-mini-calendar data-month="{{ $calendarMonth->format('Y-m') }}" data-today="{{ now()->toDateString() }}" data-events="{{ $calendarEvents->toJson() }}" data-calendar-url="{{ route('calendar') }}">
                <div class="panel-heading calendar-heading">
                    <h2 data-calendar-title>{{ $calendarMonth->format('F Y') }}</h2>
                    <div class="calendar-controls">
                        <div class="calendar-arrows">
                            <button type="button" data-calendar-step="-1" aria-label="Previous month">‹</button>
                            <button type="button" data-calendar-step="1" aria-label="Next month">›</button>
                        </div>
                        <button type="button" class="subtle-button" data-calendar-today>Today</button>
                    </div>
                </div>
                <div class="calendar-weekdays">@foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day)<span>{{ $day }}</span>@endforeach</div>
                <div class="calendar-dates" data-calendar-dates>
                    @for ($i = 0; $i < $calendarDays; $i++)
                        @php $date = $calendarStart->copy()->addDays($i); @endphp
                        <a href="{{ route('calendar') }}#date-{{ $date->toDateString() }}" class="calendar-date {{ $date->month !== $calendarMonth->month ? 'outside-month' : '' }} {{ $date->isToday() ? 'is-today' : '' }}" aria-label="{{ $date->format('F j, Y') }}" @if($date->isToday()) aria-current="date" @endif>
                            <span>{{ $date->day }}</span>
                            <span class="calendar-event-dots">@foreach (($calendarEvents[$date->toDateString()] ?? collect())->take(3) as $priority)<i class="priority-dot {{ $priority }}"></i>@endforeach</span>
                        </a>
                    @endfor
                </div>
            </section>
        </div>

        <section class="metrics-grid" aria-label="Task statistics">
            @foreach ($stats as $label => $stat)
                @php
                    $tone = $tones[$loop->index];
                    $max = max(1, ...$stat['series']);
                    $chartValues = array_map(fn ($count) => round(48 - ($count / $max * 36), 2), $stat['series']);
                    $chartPath = 'M 0 '.$chartValues[0];
                    for ($point = 1; $point < count($chartValues); $point++) {
                        $chartPath .= ' C '.($point * 20 - 10).' '.$chartValues[$point - 1].', '.($point * 20 - 10).' '.$chartValues[$point].', '.($point * 20).' '.$chartValues[$point];
                    }
                @endphp
                <article class="dashboard-surface metric-card {{ $tone }}">
                    <span class="metric-icon"><x-icon :name="$stat['icon']" /></span>
                    <div class="metric-copy">
                        <h2>{{ $label }}</h2>
                        <strong class="metric-value" data-dashboard-count="{{ $stat['value'] }}">{{ $stat['value'] }}</strong>
                        <span class="metric-change {{ ($stat['change'] ?? 0) < 0 ? 'negative' : '' }}">@if($stat['change'] !== null){{ $stat['change'] < 0 ? '↓' : '↑' }} {{ abs($stat['change']) }}%@else — @endif</span>
                        <p>from last week</p>
                    </div>
                    <svg class="metric-chart" viewBox="0 0 120 62" role="img" aria-label="{{ $label }} created over the last seven days">
                        <defs><linearGradient id="metric-fill-{{ $loop->index }}" x1="0" x2="0" y1="0" y2="1"><stop offset="0%" stop-color="currentColor" stop-opacity=".18"/><stop offset="100%" stop-color="currentColor" stop-opacity="0"/></linearGradient></defs>
                        <path d="{{ $chartPath }} L 120 62 L 0 62 Z" fill="url(#metric-fill-{{ $loop->index }})"/>
                        <path d="{{ $chartPath }}" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                    </svg>
                </article>
            @endforeach
        </section>

        <div class="dashboard-detail-grid">
            <section class="dashboard-surface today-panel">
                <div class="panel-heading today-heading">
                    <div class="heading-with-count"><h2>Today's Tasks</h2><span class="count-badge">{{ $todayTasks->count() }} {{ \Illuminate\Support\Str::plural('task', $todayTasks->count()) }}</span></div>
                    <a class="panel-link" href="{{ route('tasks.today') }}">View All <span>→</span></a>
                </div>
                <div class="today-task-list">
                    @forelse ($todayTasks->take(5) as $task)
                        <div class="today-task-row {{ $task->status === 'completed' ? 'task-is-complete' : '' }}">
                            <form method="POST" action="{{ route('tasks.toggle-status', $task) }}">
                                @csrf @method('PATCH')
                                <button class="task-check" type="submit" aria-label="{{ $task->status === 'completed' ? 'Reopen' : 'Complete' }} {{ $task->title }}" aria-pressed="{{ $task->status === 'completed' ? 'true' : 'false' }}">@if($task->status === 'completed')<x-icon name="check" />@endif</button>
                            </form>
                            <span class="priority-dot {{ $task->priority }}"></span>
                            <a class="task-row-copy" href="{{ route('tasks.show', $task) }}"><strong>{{ $task->title }}</strong><span><x-icon name="clipboard" /> {{ $task->category ?: 'General' }}</span></a>
                            <span class="task-due"><x-icon name="calendar" /> Today</span>
                            <a href="{{ route('tasks.edit', $task) }}" data-edit-task-id="{{ $task->id }}" class="task-more" aria-label="Edit {{ $task->title }}"><x-icon name="more-vertical" /></a>
                        </div>
                    @empty
                        <div class="dashboard-empty"><span class="empty-icon"><x-icon name="check-circle" /></span><h3>A little room to focus</h3><p>You have no tasks scheduled for today.</p><a href="{{ route('tasks.create') }}" data-open-task-modal class="panel-link">Create a task <span>→</span></a></div>
                    @endforelse
                </div>
                @if($todayTasks->count() > 5)<a class="today-overflow panel-link" href="{{ route('tasks.today') }}">View {{ $todayTasks->count() - 5 }} more tasks →</a>@endif
            </section>

            <section class="dashboard-surface progress-panel" id="task-progress">
                <div class="panel-heading"><h2>Task Progress</h2><a href="{{ route('all-tasks') }}" class="subtle-button">All Tasks</a></div>
                <div class="progress-content">
                    <div class="task-donut {{ $total === 0 ? 'is-empty' : '' }}" style="--completed: {{ $completedPercent }}%; --active: {{ $completedPercent + $activePercent }}%" role="img" aria-label="{{ $total }} tasks: {{ $completed }} completed, {{ $inProgress }} in progress, {{ $pending }} pending"><div><strong>{{ $total }}</strong><span>Tasks</span></div></div>
                    <div class="progress-legend">
                        @foreach ([['Completed', $completed, 'completed'], ['In Progress', $inProgress, 'in-progress'], ['Pending', $pending, 'pending'], ['Overdue', $overdueCount, 'overdue']] as [$label, $count, $status])
                            <div><span class="legend-dot {{ $status }}"></span><span>{{ $label }}</span><strong>{{ $count }}</strong><span class="legend-percent">{{ $total ? round($count / $total * 100) : 0 }}%</span></div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="dashboard-surface priority-panel">
                <div class="panel-heading"><h2>Priority Tasks</h2><a class="panel-link" href="{{ route('priority') }}">View All <span>→</span></a></div>
                <div class="priority-task-list">
                    @forelse($priorityTasks as $task)
                        <div class="priority-task-row">
                            <x-icon name="flag" class="priority-flag {{ $task->priority }}" />
                            <a class="task-row-copy" href="{{ route('tasks.show', $task) }}"><strong>{{ $task->title }}</strong><span>{{ $task->due_date?->isToday() ? 'Today' : ($task->due_date?->isTomorrow() ? 'Tomorrow' : ($task->due_date?->format('M j') ?? 'No due date')) }}</span></a>
                            <span class="priority-badge {{ $task->priority }}">{{ ucfirst($task->priority) }}</span>
                            <a class="task-more" href="{{ route('tasks.edit', $task) }}" data-edit-task-id="{{ $task->id }}" aria-label="Edit {{ $task->title }}"><x-icon name="more-vertical" /></a>
                        </div>
                    @empty
                        <p class="compact-empty">All clear. No priority tasks right now.</p>
                    @endforelse
                </div>
            </section>

            <section class="dashboard-surface activity-panel">
                <div class="panel-heading"><h2>Recent Activity</h2><a class="panel-link" href="{{ route('all-tasks') }}">View All <span>→</span></a></div>
                <div class="compact-list">
                    @forelse($recentTasks as $task)
                        @php $isNew = $task->created_at->equalTo($task->updated_at); $done = $task->status === 'completed'; @endphp
                        <a class="activity-row" href="{{ route('tasks.edit', $task) }}"><span class="activity-icon {{ $done ? 'done' : ($isNew ? 'new' : 'updated') }}"><x-icon :name="$done ? 'check' : ($isNew ? 'clipboard' : 'edit')" /></span><span>You {{ $done ? 'completed' : ($isNew ? 'created' : 'updated') }} a task <strong>{{ $task->title }}</strong></span><time datetime="{{ $task->updated_at->toIso8601String() }}">{{ $task->updated_at->diffForHumans() }}</time></a>
                    @empty
                        <p class="compact-empty">Your task activity will appear here.</p>
                    @endforelse
                </div>
            </section>

            <section class="dashboard-surface schedule-panel">
                <div class="panel-heading"><h2>Upcoming Schedule</h2><a class="panel-link" href="{{ route('calendar') }}">View Calendar <span>→</span></a></div>
                <div class="compact-list">
                    @forelse($upcomingTasks as $task)
                        <a class="schedule-row" href="{{ route('tasks.edit', $task) }}"><span class="priority-dot {{ $task->priority }}"></span><strong>{{ $task->title }}</strong><time datetime="{{ $task->due_date->toDateString() }}">{{ $task->due_date->isToday() ? 'Today' : ($task->due_date->isTomorrow() ? 'Tomorrow' : $task->due_date->format('M j')) }}</time></a>
                    @empty
                        <p class="compact-empty">No upcoming tasks. Enjoy the breathing room.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@endsection
