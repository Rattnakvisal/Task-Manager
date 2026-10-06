<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('task pages require authentication', function (string $route) {
    $this->get(route($route))->assertRedirect(route('login'));
})->with(['tasks.today', 'tasks.overdue', 'tasks.completed', 'tasks.create']);

test('the home route redirects to the canonical dashboard and the old completed page is removed', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/')->assertRedirect('/dashboard');
    $this->get('/completed')->assertNotFound();
});

test('focused task pages have accurate scopes and never include another users tasks', function () {
    $this->travelTo(now()->setDate(2026, 10, 4)->startOfDay());
    $user = User::factory()->create();
    foreach ([
        ['title' => 'Today pending', 'due_date' => today(), 'status' => 'pending'],
        ['title' => 'Today completed', 'due_date' => today(), 'status' => 'completed'],
        ['title' => 'Late active', 'due_date' => today()->subDay(), 'status' => 'in_progress'],
        ['title' => 'Past completed', 'due_date' => today()->subDay(), 'status' => 'completed'],
        ['title' => 'Tomorrow pending', 'due_date' => today()->addDay(), 'status' => 'pending'],
        ['title' => 'Unscheduled pending', 'due_date' => null, 'status' => 'pending'],
    ] as $attributes) {
        $user->tasks()->create($attributes + ['priority' => 'medium']);
    }
    $other = User::factory()->create();
    foreach (['pending', 'completed'] as $status) {
        foreach ([today(), today()->subDay()] as $dueDate) {
            $other->tasks()->create(['title' => 'Private unrelated task', 'status' => $status, 'priority' => 'high', 'due_date' => $dueDate]);
        }
    }
    $this->actingAs($user);

    foreach ([
        'today' => ['Today pending', 'Today completed'],
        'overdue' => ['Late active'],
        'completed' => ['Today completed', 'Past completed'],
    ] as $section => $titles) {
        $this->get(route('tasks.'.$section, ['status' => 'pending']))
            ->assertOk()
            ->assertDontSee('Private unrelated task')
            ->assertViewHas('total', count($titles))
            ->assertViewHas('tasks', fn ($tasks) => $tasks->pluck('title')->sort()->values()->all() === collect($titles)->sort()->values()->all());
    }
    $this->travelBack();
});

test('search and priority filters stay inside the requested task page', function () {
    $user = User::factory()->create();
    foreach ([
        ['title' => 'Review design', 'description' => null, 'status' => 'completed', 'priority' => 'high'],
        ['title' => 'Finished notes', 'description' => 'Design review notes', 'status' => 'completed', 'priority' => 'low'],
        ['title' => 'Active design', 'description' => null, 'status' => 'pending', 'priority' => 'high'],
    ] as $attributes) {
        $user->tasks()->create($attributes);
    }
    User::factory()->create()->tasks()->create(['title' => 'Private design', 'description' => 'design', 'status' => 'completed', 'priority' => 'high']);

    $this->actingAs($user)->get(route('tasks.completed', ['q' => 'design', 'priority' => 'high']))
        ->assertOk()
        ->assertViewHas('total', 2)
        ->assertViewHas('tasks', fn ($tasks) => $tasks->pluck('title')->all() === ['Review design']);
});

test('focused task pages paginate and preserve their filters', function () {
    $user = User::factory()->create();
    foreach (range(1, 13) as $index) {
        $user->tasks()->create(['title' => 'Finished review '.$index, 'status' => 'completed', 'priority' => 'high']);
    }

    $this->actingAs($user)->get(route('tasks.completed', ['q' => 'review', 'priority' => 'high', 'page' => 2]))
        ->assertOk()
        ->assertViewHas('tasks', fn ($tasks) => $tasks->count() === 1 && $tasks->total() === 13 && str_contains($tasks->url(1), 'priority=high') && str_contains($tasks->url(1), 'q=review'));
});

test('task details support checklist progress and completion without javascript', function () {
    $user = User::factory()->create();
    $task = $user->tasks()->create([
        'title' => 'Ship release', 'description' => 'Verify release notes.', 'status' => 'pending', 'priority' => 'high',
        'subtasks' => [['id' => 'review', 'title' => 'Review notes', 'completed' => false]],
    ]);
    $this->actingAs($user);

    $this->get(route('tasks.show', $task))->assertOk()->assertSee('Review notes');
    $this->from(route('tasks.show', $task))->patch(route('tasks.toggle-subtask', [$task, 'review']))
        ->assertRedirect(route('tasks.show', $task));
    expect($task->fresh()->completed_subtasks_count)->toBe(1);
    $this->from(route('tasks.show', $task))->patch(route('tasks.toggle-status', $task))
        ->assertRedirect(route('tasks.show', $task));
    $this->get(route('tasks.completed'))->assertSee('Ship release');
    $this->get(route('tasks.show', $task))->assertSee('data-task-status="completed"', false);

    $this->actingAs(User::factory()->create())->get(route('tasks.show', $task))->assertNotFound();
});

test('the create task page saves a task and displays validation errors', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('tasks.create'))->assertOk()->assertSee('Create Task')->assertSee('name="end_date"', false);

    $this->from(route('tasks.create'))->post(route('tasks.store'), [
        'title' => 'Plan sprint', 'priority' => 'high', 'status' => 'pending',
        'due_date' => '2026-10-04', 'end_date' => '2026-10-05',
    ])->assertRedirect(route('tasks.index'));
    $this->assertDatabaseHas('tasks', ['user_id' => $user->id, 'title' => 'Plan sprint']);

    $this->followingRedirects()->from(route('tasks.create'))->post(route('tasks.store'), [
        'title' => '', 'priority' => 'medium', 'status' => 'pending',
    ])->assertOk()->assertSee('The title field is required.');
});

test('dashboard navigation links to task pages and removes unrelated workspace menus', function () {
    $this->actingAs(User::factory()->create())->get(route('dashboard'))
        ->assertOk()
        ->assertSee('WorkMind')
        ->assertSee('Nova')
        ->assertSee(route('tasks.today'), false)
        ->assertSee(route('tasks.overdue'), false)
        ->assertSee(route('tasks.completed'), false)
        ->assertSee(route('tasks.create'), false)
        ->assertDontSee('<span>Projects</span>', false)
        ->assertDontSee('<span>Team</span>', false)
        ->assertDontSee('<span>Analytics</span>', false)
        ->assertDontSee('<span>Settings</span>', false);
});

test('authenticated application renders page and chatbot loading skeletons', function () {
    $this->actingAs(User::factory()->create())->get(route('dashboard'))
        ->assertOk()
        ->assertSee('id="page-loading-skeleton"', false)
        ->assertSee('class="skeleton-block', false)
        ->assertSee('id="ai-chat-history-skeleton"', false)
        ->assertSee('Loading workspace...', false)
        ->assertSee('Loading conversation...', false);
});

test('task page export dropdown is click accessible', function () {
    $this->actingAs(User::factory()->create())->get(route('tasks.index'))
        ->assertOk()
        ->assertSee('data-dropdown-toggle', false)
        ->assertSee('aria-haspopup="menu"', false)
        ->assertSee('data-dropdown-menu', false)
        ->assertSee('role="menuitem"', false);
});

test('projects and analytics pages render properly with user data', function () {
    $user = User::factory()->create();
    $user->tasks()->createMany([
        ['title' => 'Dev task 1', 'category' => 'Dev', 'status' => 'completed', 'priority' => 'high'],
        ['title' => 'Design task 1', 'category' => 'Design', 'status' => 'in_progress', 'priority' => 'medium'],
    ]);

    $this->actingAs($user);

    $this->get(route('projects'))
        ->assertOk()
        ->assertSee('Projects & Categories')
        ->assertSee('Dev')
        ->assertSee('Design');

    $this->get(route('analytics'))
        ->assertOk()
        ->assertSee('Analytics & Productivity')
        ->assertSee('Productivity Score');
});
