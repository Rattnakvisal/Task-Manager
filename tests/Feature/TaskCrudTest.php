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
