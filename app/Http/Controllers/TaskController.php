<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;

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
        $tasks = $request->user()->tasks()->orderBy('due_date')->orderByDesc('created_at')->get();
        $stats = [
            'Total Tasks' => ['value' => $tasks->count(), 'label' => 'All tasks', 'icon' => 'clipboard', 'tone' => 'bg-blue-50 text-blue-600'],
            'Pending' => ['value' => $tasks->where('status', 'pending')->count(), 'label' => 'Needs attention', 'icon' => 'list', 'tone' => 'bg-amber-50 text-amber-600'],
            'In Progress' => ['value' => $tasks->where('status', 'in_progress')->count(), 'label' => 'Active work', 'icon' => 'layers', 'tone' => 'bg-cyan-50 text-cyan-600'],
            'Completed' => ['value' => $tasks->where('status', 'completed')->count(), 'label' => 'Tasks done', 'icon' => 'check-circle', 'tone' => 'bg-emerald-50 text-emerald-600'],
        ];
        $upcomingTasks = $tasks->whereNotNull('due_date')->where('status', '!=', 'completed')->take(5);
        $priorityTasks = $tasks->where('priority', 'high')->where('status', '!=', 'completed')->take(5);
        $recentTasks = $request->user()->tasks()->latest()->take(5)->get();

        return view('tasks.dashboard', compact('stats', 'upcomingTasks', 'priorityTasks', 'recentTasks'));
    }

    public function calendar(Request $request)
    {
        $tasks = $request->user()->tasks()->whereNotNull('due_date')
            ->orderBy('due_date')
            ->orderByDesc('created_at')
            ->get();
        $groupedTasks = $tasks->groupBy(fn (Task $task) => $task->due_date->format('Y-m-d'));
        $unscheduledTasks = $request->user()->tasks()->whereNull('due_date')->orderByDesc('created_at')->get();

        return view('tasks.calendar', compact('groupedTasks', 'unscheduledTasks'));
    }

    public function priority(Request $request)
    {
        $tasksByPriority = [
            'high' => $request->user()->tasks()->where('priority', 'high')->orderBy('due_date')->orderByDesc('created_at')->get(),
            'medium' => $request->user()->tasks()->where('priority', 'medium')->orderBy('due_date')->orderByDesc('created_at')->get(),
            'low' => $request->user()->tasks()->where('priority', 'low')->orderBy('due_date')->orderByDesc('created_at')->get(),
        ];

        return view('tasks.priority', compact('tasksByPriority'));
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

        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($builder) use ($search) {
                $builder->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $tasks = $query->orderBy('due_date')->orderByDesc('created_at')->get();

        return view('tasks.all', compact('tasks'));
    }

    public function completed(Request $request)
    {
        return $this->taskPage($request, [
            'section' => 'completed',
            'title' => 'Completed Tasks',
            'subtitle' => 'Review finished work and completed milestones.',
            'eyebrow' => 'Done',
            'status' => 'completed',
        ]);
    }

    private function taskPage(Request $request, array $page)
    {
        $allTasks = $request->user()->tasks()->get();
        $query = $request->user()->tasks();

        $status = $page['status'] ?? $request->status;
        $priority = $page['priority'] ?? $request->priority;

        if ($status) {
            $query->where('status', $status);
        }

        if ($priority) {
            $query->where('priority', $priority);
        }

        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($builder) use ($search) {
                $builder->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($page['with_due_date'] ?? false) {
            $query->whereNotNull('due_date');
        }

        $tasks = $query->orderBy('due_date')->orderByDesc('created_at')->get();
        $stats = [
            'Total' => ['value' => $allTasks->count(), 'label' => 'All tasks', 'icon' => 'clipboard', 'tone' => 'bg-blue-50 text-blue-600'],
            'In Progress' => ['value' => $allTasks->where('status', 'in_progress')->count(), 'label' => 'Active work', 'icon' => 'layers', 'tone' => 'bg-cyan-50 text-cyan-600'],
            'Completed' => ['value' => $allTasks->where('status', 'completed')->count(), 'label' => 'Tasks done', 'icon' => 'check-circle', 'tone' => 'bg-emerald-50 text-emerald-600'],
        ];
        $activeStatus = $status;
        $activePriority = $priority;

        return view('tasks.index', compact('tasks', 'stats', 'page', 'activeStatus', 'activePriority'));
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
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high',
            'status' => 'required|in:pending,in_progress,completed',
            'due_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:due_date',
        ]);

        $validated['user_id'] = $request->user()->id;

        $task = Task::create($validated);

        return redirect()
            ->route('tasks.index')
            ->with('success', 'Task created successfully.')
            ->with('created_task_id', $task->id);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Task $task)
    {
        $this->ensureTaskOwner($request, $task);

        return redirect()->route('tasks.edit', $task);
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
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high',
            'status' => 'required|in:pending,in_progress,completed',
            'due_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:due_date',
        ]);

        $task->update($validated);

        return redirect()->route('tasks.index')->with('success', 'Task Updated Successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Task $task)
    {
        $this->ensureTaskOwner($request, $task);
        $task->delete();

        return redirect()->route('tasks.index')->with('success', 'Task Deleted Successfully.');
    }

    private function ensureTaskOwner(Request $request, Task $task): void
    {
        abort_unless($task->user_id === $request->user()->id, 404);
    }
}
