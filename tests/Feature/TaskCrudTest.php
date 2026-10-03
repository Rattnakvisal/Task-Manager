<?php

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createTaskForCrudTest(array $attributes = []): Task
{
    $user = User::factory()->create();

    return Task::create(array_merge([
        'user_id' => $user->id,
        'title' => 'Prepare project report',
        'description' => 'Compile the weekly project notes.',
        'priority' => 'medium',
        'status' => 'pending',
        'due_date' => '2026-08-20',
        'end_date' => '2026-08-21',
    ], $attributes));
}

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('the task edit page is available without javascript', function () {
    $task = createTaskForCrudTest(['user_id' => $this->user->id]);

    $this->get(route('tasks.edit', $task))
        ->assertOk()
        ->assertSee('Edit Task')
        ->assertSee($task->title)
        ->assertSee(route('tasks.update', $task), false);
});

test('the task show route leads to the edit page', function () {
    $task = createTaskForCrudTest(['user_id' => $this->user->id]);

    $this->get(route('tasks.show', $task))
        ->assertRedirect(route('tasks.edit', $task));
});

test('a task can be updated from the edit form', function () {
    $task = createTaskForCrudTest(['user_id' => $this->user->id]);

    $this->put(route('tasks.update', $task), [
        'title' => 'Publish project report',
        'description' => 'Final version.',
        'priority' => 'high',
        'status' => 'completed',
        'due_date' => '2026-08-22',
        'end_date' => '2026-08-23',
    ])->assertRedirect(route('tasks.index'));

    expect($task->fresh())
        ->title->toBe('Publish project report')
        ->priority->toBe('high')
        ->status->toBe('completed');
});

test('a task can be quickly created via quickStore', function () {
    $response = $this->post(route('tasks.quick-store'), [
        'title' => 'Quick review with client',
        'priority' => 'high',
        'category' => 'work',
        'due_date' => '2026-09-01',
    ]);

    $response->assertRedirect();
    $task = Task::where('title', 'Quick review with client')->first();
    expect($task)->not->toBeNull()
        ->user_id->toBe($this->user->id)
        ->priority->toBe('high')
        ->category->toBe('work');
});

test('a task status can be toggled via toggleStatus route', function () {
    $task = createTaskForCrudTest(['user_id' => $this->user->id, 'status' => 'pending']);

    // 1-click toggle: pending -> completed
    $response = $this->patchJson(route('tasks.toggle-status', $task));
    $response->assertOk()
        ->assertJson([
            'success' => true,
            'status' => 'completed',
        ]);
    expect($task->fresh()->status)->toBe('completed');

    // 1-click toggle: completed -> pending
    $response = $this->patchJson(route('tasks.toggle-status', $task));
    $response->assertOk()
        ->assertJson([
            'success' => true,
            'status' => 'pending',
        ]);
    expect($task->fresh()->status)->toBe('pending');

    // Direct status update (as used by Kanban drag & drop)
    $response = $this->patchJson(route('tasks.toggle-status', $task), ['status' => 'in_progress']);
    $response->assertOk()
        ->assertJson([
            'success' => true,
            'status' => 'in_progress',
        ]);
    expect($task->fresh()->status)->toBe('in_progress');
});

test('a task can be pinned and unpinned', function () {
    $task = createTaskForCrudTest(['user_id' => $this->user->id, 'is_pinned' => false]);

    $response = $this->patchJson(route('tasks.toggle-pin', $task));
    $response->assertOk()
        ->assertJson([
            'success' => true,
            'is_pinned' => true,
        ]);
    expect($task->fresh()->is_pinned)->toBeTrue();

    $response = $this->patchJson(route('tasks.toggle-pin', $task));
    $response->assertOk()
        ->assertJson([
            'success' => true,
            'is_pinned' => false,
        ]);
    expect($task->fresh()->is_pinned)->toBeFalse();
});

test('subtasks can be toggled within a task', function () {
    $task = createTaskForCrudTest([
        'user_id' => $this->user->id,
        'subtasks' => [
            ['id' => 'st-1', 'title' => 'First step', 'completed' => false],
            ['id' => 'st-2', 'title' => 'Second step', 'completed' => false],
        ],
    ]);

    $response = $this->patchJson(route('tasks.toggle-subtask', ['task' => $task, 'subtaskId' => 'st-1']));
    $response->assertOk()
        ->assertJson([
            'success' => true,
            'progress' => 50,
            'completed_count' => 1,
            'total_count' => 2,
        ]);

    $freshTask = $task->fresh();
    expect($freshTask->subtasks[0]['completed'])->toBeTrue();
    expect($freshTask->subtasks[1]['completed'])->toBeFalse();
});

test('tasks can be exported to csv and json', function () {
    createTaskForCrudTest(['user_id' => $this->user->id, 'title' => 'Task A']);
    createTaskForCrudTest(['user_id' => $this->user->id, 'title' => 'Task B']);

    $csvResponse = $this->get(route('tasks.export', ['format' => 'csv']));
    $csvResponse->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    expect($csvResponse->streamedContent())->toContain('Task A', 'Task B');

    $jsonResponse = $this->get(route('tasks.export', ['format' => 'json']));
    $jsonResponse->assertOk()
        ->assertHeader('Content-Type', 'application/json');
    expect($jsonResponse->streamedContent())->toContain('Task A', 'Task B');
});


