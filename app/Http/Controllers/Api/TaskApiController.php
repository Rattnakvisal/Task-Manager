<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskApiController extends Controller
{
    public function dashboard(Request $request)
    {
        $payload = $this->payload($request);
        $tasks = $request->user()->tasks()->orderBy('due_date')->orderByDesc('created_at')->get();

        $payload['upcoming_tasks'] = $tasks
            ->whereNotNull('due_date')
            ->where('status', '!=', 'completed')
            ->take(5)
            ->map(fn (Task $task) => $this->serializeTask($task))
            ->values();
        $payload['priority_tasks'] = $tasks
            ->where('priority', 'high')
            ->where('status', '!=', 'completed')
            ->take(5)
            ->map(fn (Task $task) => $this->serializeTask($task))
            ->values();
        $payload['recent_tasks'] = $request->user()->tasks()->latest()
            ->take(5)
            ->get()
            ->map(fn (Task $task) => $this->serializeTask($task))
            ->values();

        return response()->json($payload);
    }

    public function calendar(Request $request)
    {
        return response()->json($this->payload($request, ['with_due_date' => true]));
    }

    public function priority(Request $request)
    {
        return response()->json($this->payload($request, ['priority' => $request->priority ?? 'high']));
    }

    public function allTasks(Request $request)
    {
        return response()->json($this->payload($request));
    }

    public function completed(Request $request)
    {
        return response()->json($this->payload($request, ['status' => 'completed']));
    }

    public function search(Request $request)
    {
        return response()->json($this->payload($request));
    }

    public function notifications(Request $request)
    {
        $tasks = $this->notificationTasks($request);

        return response()->json([
            'count' => $tasks->count(),
            'notifications' => $tasks->map(function (Task $task) {
                return [
                    'type' => $task->end_date->isToday() ? 'due_today' : 'due_tomorrow',
                    'message' => $task->end_date->isToday()
                        ? 'Task ends today.'
                        : 'Task ends tomorrow.',
                    'task' => $this->serializeTask($task),
                ];
            })->values(),
        ]);
    }

    public function index(Request $request)
    {
        return response()->json($this->payload($request));
    }

    public function store(Request $request)
    {
        $task = Task::create($this->validated($request) + [
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Task created successfully.',
            'task' => $this->serializeTask($task),
        ], 201);
    }

    public function show(Request $request, Task $task)
    {
        $this->ensureTaskOwner($request, $task);

        return response()->json(['task' => $this->serializeTask($task)]);
    }

    public function update(Request $request, Task $task)
    {
        $this->ensureTaskOwner($request, $task);
        $task->update($this->validated($request));

        return response()->json([
            'message' => 'Task updated successfully.',
            'task' => $this->serializeTask($task->fresh()),
        ]);
    }

    public function destroy(Request $request, Task $task)
    {
        $this->ensureTaskOwner($request, $task);
        $task->delete();

        return response()->json(['message' => 'Task deleted successfully.']);
    }

    private function payload(Request $request, array $forcedFilters = []): array
    {
        $allTasks = $request->user()->tasks()->get();
        $query = $request->user()->tasks();

        $status = $forcedFilters['status'] ?? $request->status;
        $priority = $forcedFilters['priority'] ?? $request->priority;

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

        if ($forcedFilters['with_due_date'] ?? false) {
            $query->whereNotNull('due_date');
        }

        $tasks = $query->orderBy('due_date')->orderByDesc('created_at')->get();

        return [
            'stats' => [
                'total' => $allTasks->count(),
                'pending' => $allTasks->where('status', 'pending')->count(),
                'in_progress' => $allTasks->where('status', 'in_progress')->count(),
                'completed' => $allTasks->where('status', 'completed')->count(),
                'high_priority' => $allTasks->where('priority', 'high')->count(),
            ],
            'filters' => [
                'status' => $status,
                'priority' => $priority,
                'q' => $request->q,
            ],
            'tasks' => $tasks->map(fn (Task $task) => $this->serializeTask($task))->values(),
        ];
    }

    private function serializeTask(Task $task): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'priority' => $task->priority,
            'status' => $task->status,
            'category' => $task->category,
            'subtasks' => $task->subtasks ?? [],
            'subtasks_count' => $task->subtasks_count,
            'completed_subtasks_count' => $task->completed_subtasks_count,
            'subtasks_progress' => $task->subtasks_progress,
            'tags' => $task->tags ?? [],
            'is_pinned' => (bool) $task->is_pinned,
            'is_overdue' => (bool) $task->is_overdue,
            'due_date' => $task->due_date?->format('Y-m-d'),
            'end_date' => $task->end_date?->format('Y-m-d'),
            'created_at' => $task->created_at?->toISOString(),
            'updated_at' => $task->updated_at?->toISOString(),
        ];
    }

    private function validated(Request $request): array
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

        if (isset($validated['subtasks']) && is_string($validated['subtasks'])) {
            $decoded = json_decode($validated['subtasks'], true);
            $validated['subtasks'] = is_array($decoded) ? $decoded : [];
        }

        if (isset($validated['tags']) && is_string($validated['tags'])) {
            $decoded = json_decode($validated['tags'], true);
            if (is_array($decoded)) {
                $validated['tags'] = $decoded;
            } else {
                $validated['tags'] = array_values(array_filter(array_map('trim', explode(',', $validated['tags']))));
            }
        }

        return $validated;
    }

    private function notificationTasks(Request $request)
    {
        return $request->user()->tasks()->whereNotNull('end_date')
            ->where('status', '!=', 'completed')
            ->where(function ($query) {
                $query->whereDate('end_date', now()->toDateString())
                    ->orWhereDate('end_date', now()->addDay()->toDateString());
            })
            ->orderBy('end_date')
            ->orderByDesc('created_at')
            ->get();
    }

    private function ensureTaskOwner(Request $request, Task $task): void
    {
        abort_unless($task->user_id === $request->user()->id, 404);
    }
}
