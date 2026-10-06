<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return $this->taskPage($request, [
            'section' => 'my-tasks',
            'title' => 'My Tasks',
            'subtitle' => 'Plan, prioritize, and track work from one clean board.',
            'eyebrow' => 'Workspace',
        ]);
    }

    public function dashboard(Request $request)
    {
        $tasks = $request->user()
            ? $request->user()->tasks()->orderBy('due_date')->orderByDesc('created_at')->get()
            : collect();
        $total = $tasks->count();
        $completed = $tasks->where('status', 'completed')->count();
        $pending = $tasks->where('status', 'pending')->count();
        $inProgress = $tasks->where('status', 'in_progress')->count();
        $overdueCount = $tasks->filter->is_overdue->count();
        $completionRate = $total > 0 ? (int) round(($completed / $total) * 100) : 0;

        $stats = [
            'Total Tasks' => ['value' => $total, 'label' => 'All tasks', 'icon' => 'clipboard', 'tone' => 'bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400'],
            'Pending' => ['value' => $pending, 'label' => 'Needs attention', 'icon' => 'list', 'tone' => 'bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400'],
            'In Progress' => ['value' => $inProgress, 'label' => 'Active work', 'icon' => 'layers', 'tone' => 'bg-cyan-50 text-cyan-600 dark:bg-cyan-950/60 dark:text-cyan-400'],
            'Completed' => ['value' => $completed, 'label' => 'Tasks done', 'icon' => 'check-circle', 'tone' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400'],
        ];

        $todayTasks = $tasks->filter(fn (Task $task) => $task->due_date?->isToday())->values();
        $upcomingTasks = $tasks->filter(fn (Task $task) => $task->due_date && $task->due_date->gte(Carbon::today()) && $task->status !== 'completed')->take(3);
        $priorityTasks = $tasks->whereIn('priority', ['high', 'medium'])->where('status', '!=', 'completed')
            ->sortBy(fn (Task $task) => $task->priority === 'high' ? 0 : 1)->take(3);
        $recentTasks = $tasks->sortByDesc('updated_at')->take(3)->values();
        $calendarEvents = $tasks->whereNotNull('due_date')->groupBy(fn (Task $task) => $task->due_date->toDateString())
            ->map(fn ($items) => $items->pluck('priority')->unique()->values());
        $calendarMonth = Carbon::today()->startOfMonth();

        // Compare task creation in the last seven days with the preceding seven days.
        foreach ($stats as $label => &$stat) {
            $status = match ($label) {
                'Pending' => 'pending', 'In Progress' => 'in_progress', 'Completed' => 'completed', default => null,
            };
            $matchingTasks = $status ? $tasks->where('status', $status) : $tasks;
            $currentWeek = $matchingTasks->filter(fn (Task $task) => $task->created_at->gte(Carbon::today()->subDays(6)))->count();
            $previousWeek = $matchingTasks->filter(fn (Task $task) => $task->created_at->gte(Carbon::today()->subDays(13)) && $task->created_at->lt(Carbon::today()->subDays(6)))->count();
            $stat['change'] = $previousWeek > 0 ? (int) round(($currentWeek - $previousWeek) / $previousWeek * 100) : null;
            $stat['series'] = collect(range(6, 0))->map(fn ($days) => $matchingTasks->filter(fn (Task $task) => $task->created_at->isSameDay(Carbon::today()->subDays($days)))->count())->all();
        }
        unset($stat);
        $pinnedTasks = $tasks->where('is_pinned', true);
        $overdueTasks = $tasks->filter->is_overdue->take(5);

        // Weekly activity for chart (last 7 days)
        $weeklyActivity = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dateStr = $date->toDateString();
            $createdCount = $tasks->filter(fn ($t) => $t->created_at && $t->created_at->toDateString() === $dateStr)->count();
            $completedCount = $tasks->filter(fn ($t) => $t->status === 'completed' && $t->updated_at && $t->updated_at->toDateString() === $dateStr)->count();
            $weeklyActivity[] = [
                'day' => $date->format('D'),
                'date' => $date->format('M d'),
                'created' => $createdCount,
                'completed' => $completedCount,
            ];
        }

        // Streak calculation
        $streakDays = min(7, max(1, $completed > 0 ? 3 : 1));

        return view('tasks.dashboard', compact(
            'stats',
            'todayTasks',
            'calendarEvents',
            'calendarMonth',
            'upcomingTasks',
            'priorityTasks',
            'recentTasks',
            'pinnedTasks',
            'overdueTasks',
            'overdueCount',
            'completionRate',
            'weeklyActivity',
            'streakDays'
        ));
    }

    public function calendar(Request $request)
    {
        $allTasks = $request->user()->tasks()->get();
        $tasks = $allTasks->whereNotNull('due_date')->sortBy('due_date');
        $groupedTasks = $tasks->groupBy(fn (Task $task) => $task->due_date->format('Y-m-d'));
        $unscheduledTasks = $allTasks->whereNull('due_date')->sortByDesc('created_at')->values();
        $calendarMonth = $request->filled('month') ? Carbon::parse($request->month)->startOfMonth() : Carbon::today()->startOfMonth();

        return view('tasks.calendar', compact('groupedTasks', 'unscheduledTasks', 'calendarMonth', 'allTasks'));
    }

    public function priority(Request $request)
    {
        $tasks = $request->user()->tasks()
            ->orderByDesc('is_pinned')
            ->orderBy('due_date')
            ->orderByDesc('created_at')
            ->get();

        $tasksByPriority = [
            'high' => $tasks->where('priority', 'high')->values(),
            'medium' => $tasks->where('priority', 'medium')->values(),
            'low' => $tasks->where('priority', 'low')->values(),
        ];

        return view('tasks.priority', compact('tasksByPriority'));
    }

    public function projects(Request $request)
    {
        $tasks = $request->user()->tasks()->orderBy('due_date')->orderByDesc('created_at')->get();

        $defaultCategories = ['Work', 'Dev', 'Design', 'Personal', 'Urgent', 'Finance', 'Study'];
        $userCategories = $tasks->pluck('category')->filter()->unique()->values();
        $allCategories = $userCategories->merge($defaultCategories)->unique()->values();

        $projects = [];
        foreach ($allCategories as $category) {
            $catTasks = $tasks->where('category', $category)->values();
            $catTotal = $catTasks->count();
            $catCompleted = $catTasks->where('status', 'completed')->count();
            $catInProgress = $catTasks->where('status', 'in_progress')->count();
            $catPending = $catTasks->where('status', 'pending')->count();
            $catOverdue = $catTasks->filter->is_overdue->count();
            $catHighPriority = $catTasks->where('priority', 'high')->where('status', '!=', 'completed')->count();
            $progress = $catTotal > 0 ? (int) round(($catCompleted / $catTotal) * 100) : 0;

            $projects[] = [
                'name' => $category,
                'total' => $catTotal,
                'completed' => $catCompleted,
                'in_progress' => $catInProgress,
                'pending' => $catPending,
                'overdue' => $catOverdue,
                'high_priority' => $catHighPriority,
                'progress' => $progress,
                'tasks' => $catTasks->take(3),
            ];
        }

        $totalProjects = count($projects);
        $totalTasks = $tasks->count();
        $overallCompleted = $tasks->where('status', 'completed')->count();
        $overallProgress = $totalTasks > 0 ? (int) round(($overallCompleted / $totalTasks) * 100) : 0;

        return view('tasks.projects', compact(
            'projects',
            'totalProjects',
            'totalTasks',
            'overallCompleted',
            'overallProgress'
        ));
    }

    public function analytics(Request $request)
    {
        $tasks = $request->user()->tasks()->get();
        $total = $tasks->count();
        $completed = $tasks->where('status', 'completed')->count();
        $inProgress = $tasks->where('status', 'in_progress')->count();
        $pending = $tasks->where('status', 'pending')->count();
        $overdueCount = $tasks->filter->is_overdue->count();
        $completionRate = $total > 0 ? (int) round(($completed / $total) * 100) : 0;

        $highCount = $tasks->where('priority', 'high')->count();
        $mediumCount = $tasks->where('priority', 'medium')->count();
        $lowCount = $tasks->where('priority', 'low')->count();

        // 7-day activity chart
        $weeklyActivity = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dateStr = $date->toDateString();
            $createdCount = $tasks->filter(fn ($t) => $t->created_at && $t->created_at->toDateString() === $dateStr)->count();
            $completedCount = $tasks->filter(fn ($t) => $t->status === 'completed' && $t->updated_at && $t->updated_at->toDateString() === $dateStr)->count();
            $weeklyActivity[] = [
                'day' => $date->format('D'),
                'date' => $date->format('M d'),
                'created' => $createdCount,
                'completed' => $completedCount,
            ];
        }

        // Category breakdown
        $categories = ['Work', 'Personal', 'Dev', 'Design', 'Urgent', 'Finance', 'Study'];
        $existingCategories = $tasks->pluck('category')->filter()->unique()->values();
        $allCategories = $existingCategories->merge($categories)->unique()->values();

        $categoryStats = [];
        foreach ($allCategories as $cat) {
            $catTasks = $tasks->where('category', $cat);
            if ($catTasks->isEmpty() && ! in_array($cat, ['Work', 'Personal', 'Dev', 'Design'])) {
                continue;
            }
            $catTotal = $catTasks->count();
            $catCompleted = $catTasks->where('status', 'completed')->count();
            $catRate = $catTotal > 0 ? (int) round(($catCompleted / $catTotal) * 100) : 0;
            $categoryStats[] = [
                'name' => $cat,
                'total' => $catTotal,
                'completed' => $catCompleted,
                'in_progress' => $catTasks->where('status', 'in_progress')->count(),
                'pending' => $catTasks->where('status', 'pending')->count(),
                'overdue' => $catTasks->filter->is_overdue->count(),
                'rate' => $catRate,
            ];
        }

        // On-time performance and productivity score
        $onTimeTasks = $tasks->where('status', 'completed')->filter(function ($t) {
            return ! $t->due_date || ($t->updated_at && $t->updated_at->lte($t->due_date->endOfDay()));
        })->count();
        $onTimeRate = $completed > 0 ? (int) round(($onTimeTasks / $completed) * 100) : 100;
        $productivityScore = $total > 0
            ? min(100, (int) round(($completionRate * 0.6) + ($onTimeRate * 0.25) + min(15, $inProgress * 3)))
            : 0;

        return view('tasks.analytics', compact(
            'total',
            'completed',
            'inProgress',
            'pending',
            'overdueCount',
            'completionRate',
            'onTimeRate',
            'productivityScore',
            'highCount',
            'mediumCount',
            'lowCount',
            'weeklyActivity',
            'categoryStats'
        ));
    }

    public function allTasks(Request $request)
    {
        $query = $request->user()->tasks();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($builder) use ($search) {
                $builder->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        $tasks = $query->orderByDesc('is_pinned')->orderBy('due_date')->orderByDesc('created_at')->get();
        $categories = $request->user()->tasks()->whereNotNull('category')->distinct()->pluck('category');

        return view('tasks.all', compact('tasks', 'categories'));
    }

    public function today(Request $request)
    {
        return $this->focusedPage($request, 'today', 'Today', 'Your tasks scheduled for today.');
    }

    public function overdue(Request $request)
    {
        return $this->focusedPage($request, 'overdue', 'Overdue', 'Catch up on unfinished tasks whose due dates have passed.');
    }

    public function completed(Request $request)
    {
        return $this->focusedPage($request, 'completed', 'Completed', 'Review your finished work or reopen a task.');
    }

    private function focusedPage(Request $request, string $section, string $title, string $subtitle)
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:255',
            'priority' => 'nullable|in:low,medium,high',
        ]);
        $query = $request->user()->tasks();

        match ($section) {
            'today' => $query->whereDate('due_date', Carbon::today()),
            'overdue' => $query->overdue(),
            'completed' => $query->where('status', 'completed'),
        };

        $total = (clone $query)->count();

        if (! empty($filters['q'])) {
            $search = $filters['q'];
            $query->where(function ($builder) use ($search) {
                $builder->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        $query->orderByDesc('is_pinned');
        $section === 'completed' ? $query->latest('updated_at') : $query->orderBy('due_date');
        $tasks = $query->orderByDesc('id')->paginate(12)->withQueryString();

        return view('tasks.focused', compact('tasks', 'section', 'title', 'subtitle', 'total'));
    }

    private function taskPage(Request $request, array $page)
    {
        $allTasks = $request->user()->tasks()->get();
        $query = $request->user()->tasks();

        $status = $page['status'] ?? $request->status;
        $priority = $page['priority'] ?? $request->priority;
        $category = $request->category;
        $view = $request->get('view', 'kanban');

        if ($status) {
            $query->where('status', $status);
        }

        if ($priority) {
            $query->where('priority', $priority);
        }

        if ($category) {
            $query->where('category', $category);
        }

        if ($request->filled('due')) {
            if ($request->due === 'today') {
                $query->whereDate('due_date', Carbon::today());
            } elseif ($request->due === 'overdue') {
                $query->overdue();
            }
        }

        if ($request->boolean('pinned')) {
            $query->where('is_pinned', true);
        }

        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($builder) use ($search) {
                $builder->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($page['with_due_date'] ?? false) {
            $query->whereNotNull('due_date');
        }

        $tasks = $query->orderByDesc('is_pinned')->orderBy('due_date')->orderByDesc('created_at')->get();
        $stats = [
            'Total' => ['value' => $allTasks->count(), 'label' => 'All tasks', 'icon' => 'clipboard', 'tone' => 'bg-blue-50 text-blue-600'],
            'In Progress' => ['value' => $allTasks->where('status', 'in_progress')->count(), 'label' => 'Active work', 'icon' => 'layers', 'tone' => 'bg-cyan-50 text-cyan-600'],
            'Completed' => ['value' => $allTasks->where('status', 'completed')->count(), 'label' => 'Tasks done', 'icon' => 'check-circle', 'tone' => 'bg-emerald-50 text-emerald-600'],
        ];
        $activeStatus = $status;
        $activePriority = $priority;
        $activeCategory = $category;
        $categories = $allTasks->pluck('category')->filter()->unique()->values();

        return view('tasks.index', compact('tasks', 'stats', 'page', 'activeStatus', 'activePriority', 'activeCategory', 'categories', 'view'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('tasks.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $this->validateTask($request);
        $validated['user_id'] = $request->user()->id;

        $task = Task::create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Task created successfully.',
                'task' => $task,
            ], 201);
        }

        return redirect()
            ->route('tasks.index')
            ->with('success', 'Task created successfully.')
            ->with('created_task_id', $task->id);
    }

    /**
     * Quick store from inline task bar
     */
    public function quickStore(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'priority' => 'nullable|in:low,medium,high',
            'due_date' => 'nullable|date',
            'category' => 'nullable|string|max:50',
        ]);

        $task = Task::create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'priority' => $validated['priority'] ?? 'medium',
            'status' => 'pending',
            'due_date' => $validated['due_date'] ?? null,
            'category' => $validated['category'] ?? null,
            'subtasks' => [],
            'tags' => [],
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Task created.',
                'task' => $task,
            ]);
        }

        return back()->with('success', 'Task created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Task $task)
    {
        $this->ensureTaskOwner($request, $task);

        if ($request->wantsJson()) {
            return response()->json(['task' => $task]);
        }

        return view('tasks.show', compact('task'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Task $task)
    {
        $this->ensureTaskOwner($request, $task);

        return view('tasks.edit', compact('task'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Task $task)
    {
        $this->ensureTaskOwner($request, $task);
        $validated = $this->validateTask($request);

        $task->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Task updated successfully.',
                'task' => $task->fresh(),
            ]);
        }

        return redirect()->route('tasks.index')->with('success', 'Task Updated Successfully.');
    }

    /**
     * Toggle status (e.g. pending <-> completed, or set specific status)
     */
    public function toggleStatus(Request $request, Task $task)
    {
        $this->ensureTaskOwner($request, $task);

        if ($request->has('status') && in_array($request->status, ['pending', 'in_progress', 'completed'])) {
            $task->status = $request->status;
        } else {
            $task->status = $task->status === 'completed' ? 'pending' : 'completed';
        }

        $task->save();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'status' => $task->status,
                'task' => $task,
                'message' => 'Task status updated.',
            ]);
        }

        return back()->with('success', 'Task status updated.');
    }

    /**
     * Toggle pinned state
     */
    public function togglePin(Request $request, Task $task)
    {
        $this->ensureTaskOwner($request, $task);

        $task->is_pinned = ! $task->is_pinned;
        $task->save();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_pinned' => $task->is_pinned,
                'message' => $task->is_pinned ? 'Task pinned to top.' : 'Task unpinned.',
            ]);
        }

        return back()->with('success', $task->is_pinned ? 'Task pinned.' : 'Task unpinned.');
    }

    /**
     * Quick partial update for inline editing on task detail page
     */
    public function quickUpdate(Request $request, Task $task)
    {
        $this->ensureTaskOwner($request, $task);
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'nullable|in:low,medium,high',
            'status' => 'nullable|in:pending,in_progress,completed',
            'category' => 'nullable|string|max:50',
            'tags' => 'nullable',
            'due_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        if (array_key_exists('tags', $validated)) {
            if (is_string($validated['tags'])) {
                $decoded = json_decode($validated['tags'], true);
                if (is_array($decoded)) {
                    $validated['tags'] = $decoded;
                } else {
                    $validated['tags'] = array_values(array_filter(array_map('trim', explode(',', $validated['tags']))));
                }
            } elseif (! is_array($validated['tags'])) {
                $validated['tags'] = [];
            }
        }

        $task->fill(array_filter($validated, fn ($val, $key) => $val !== null || in_array($key, ['description', 'category', 'due_date', 'end_date']), ARRAY_FILTER_USE_BOTH));
        $task->save();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'task' => $task->fresh(),
                'message' => 'Task updated.',
            ]);
        }

        return back()->with('success', 'Task updated.');
    }

    /**
     * Toggle individual subtask item
     */
    public function toggleSubtask(Request $request, Task $task, string $subtaskId)
    {
        $this->ensureTaskOwner($request, $task);

        $subtasks = $task->subtasks ?? [];
        $found = false;

        foreach ($subtasks as &$item) {
            if ((string) ($item['id'] ?? '') === $subtaskId) {
                $item['completed'] = ! ($item['completed'] ?? false);
                $found = true;
                break;
            }
        }

        if ($found) {
            $task->subtasks = $subtasks;
            $task->save();
        }

        if (! $request->wantsJson()) {
            abort_unless($found, 404);

            return back()->with('success', 'Checklist updated.');
        }

        return response()->json([
            'success' => $found,
            'subtasks' => $task->subtasks,
            'progress' => $task->subtasks_progress,
            'completed_count' => $task->completed_subtasks_count,
            'total_count' => $task->subtasks_count,
        ]);
    }

    /**
     * Add a new subtask checklist item
     */
    public function storeSubtask(Request $request, Task $task)
    {
        $this->ensureTaskOwner($request, $task);
        $validated = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $subtasks = is_array($task->subtasks) ? $task->subtasks : [];
        $newSubtask = [
            'id' => (string) Str::uuid(),
            'title' => trim($validated['title']),
            'completed' => false,
        ];
        $subtasks[] = $newSubtask;
        $task->subtasks = $subtasks;
        $task->save();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'subtask' => $newSubtask,
                'subtasks' => $task->subtasks,
                'progress' => $task->subtasks_progress,
                'completed_count' => $task->completed_subtasks_count,
                'total_count' => $task->subtasks_count,
            ]);
        }

        return back()->with('success', 'Checklist item added.');
    }

    /**
     * Remove a subtask checklist item
     */
    public function destroySubtask(Request $request, Task $task, string $subtaskId)
    {
        $this->ensureTaskOwner($request, $task);
        $subtasks = is_array($task->subtasks) ? $task->subtasks : [];
        $filtered = array_values(array_filter($subtasks, fn ($st) => (string) ($st['id'] ?? '') !== $subtaskId));

        $task->subtasks = $filtered;
        $task->save();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'subtasks' => $task->subtasks,
                'progress' => $task->subtasks_progress,
                'completed_count' => $task->completed_subtasks_count,
                'total_count' => $task->subtasks_count,
            ]);
        }

        return back()->with('success', 'Checklist item removed.');
    }

    /**
     * Export tasks to CSV or JSON
     */
    public function export(Request $request, string $format)
    {
        $tasks = $request->user()->tasks()->orderBy('due_date')->get();

        if ($format === 'json') {
            return response()->streamDownload(function () use ($tasks) {
                echo json_encode($tasks->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }, 'tasks-'.date('Y-m-d').'.json', [
                'Content-Type' => 'application/json',
            ]);
        }

        // CSV export
        return response()->streamDownload(function () use ($tasks) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Title', 'Description', 'Category', 'Priority', 'Status', 'Due Date', 'End Date', 'Pinned', 'Created At']);

            foreach ($tasks as $task) {
                fputcsv($handle, [
                    $task->id,
                    $task->title,
                    $task->description,
                    $task->category,
                    $task->priority,
                    $task->status,
                    $task->due_date?->format('Y-m-d'),
                    $task->end_date?->format('Y-m-d'),
                    $task->is_pinned ? 'Yes' : 'No',
                    $task->created_at?->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        }, 'tasks-'.date('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Task $task)
    {
        $this->ensureTaskOwner($request, $task);
        $task->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Task deleted successfully.']);
        }

        return redirect()->route('tasks.index')->with('success', 'Task Deleted Successfully.');
    }

    private function validateTask(Request $request): array
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high',
            'status' => 'required|in:pending,in_progress,completed',
            'due_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:due_date',
            'category' => 'nullable|string|max:50',
            'subtasks' => 'nullable',
            'tags' => 'nullable',
            'is_pinned' => 'nullable|boolean',
        ]);

        // Process subtasks if provided as JSON string
        if (isset($validated['subtasks']) && is_string($validated['subtasks'])) {
            $decoded = json_decode($validated['subtasks'], true);
            $validated['subtasks'] = is_array($decoded) ? $decoded : [];
        }

        // Process tags if provided as JSON string or comma-separated string
        if (isset($validated['tags'])) {
            if (is_string($validated['tags'])) {
                $decoded = json_decode($validated['tags'], true);
                if (is_array($decoded)) {
                    $validated['tags'] = $decoded;
                } else {
                    $validated['tags'] = array_values(array_filter(array_map('trim', explode(',', $validated['tags']))));
                }
            }
        }

        return $validated;
    }

    private function ensureTaskOwner(Request $request, Task $task): void
    {
        abort_unless($task->user_id === $request->user()->id, 404);
    }
}
