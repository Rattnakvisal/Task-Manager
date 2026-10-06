<?php

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests can preview the dashboard but protected features still require an account', function () {
    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Welcome to WorkMind')
        ->assertSee('Create account')
        ->assertDontSee('data-open-task-modal', false);

    foreach (['tasks.index', 'projects', 'calendar', 'priority', 'analytics', 'profile.edit'] as $route) {
        $this->get(route($route))->assertRedirect(route('login'));
    }
});

test('dashboard shows only the signed in users tasks and accurate daily panels', function () {
    $this->travelTo(now()->setDate(2026, 10, 4)->startOfDay());
    $user = User::factory()->create();
    foreach ([
        ['title' => 'Today design review', 'due_date' => today(), 'priority' => 'high', 'status' => 'pending'],
        ['title' => 'Tomorrow presentation', 'due_date' => today()->addDay(), 'priority' => 'medium', 'status' => 'in_progress'],
        ['title' => 'Past completed task', 'due_date' => today()->subDay(), 'priority' => 'low', 'status' => 'completed'],
        ['title' => 'Overdue review', 'due_date' => today()->subDays(2), 'priority' => 'high', 'status' => 'pending'],
    ] as $attributes) {
        $user->tasks()->create($attributes);
    }
    User::factory()->create()->tasks()->create(['title' => 'Private task from another user', 'due_date' => today(), 'priority' => 'high', 'status' => 'pending']);

    $response = $this->actingAs($user)->get(route('dashboard'));
    $response->assertOk()
        ->assertSee('images/Task.png', false)
        ->assertSee('October 2026')
        ->assertSee('Today design review')
        ->assertSee('Tomorrow presentation')
        ->assertDontSee('Private task from another user')
        ->assertViewHas('todayTasks', fn ($tasks) => $tasks->pluck('title')->all() === ['Today design review'])
        ->assertViewHas('upcomingTasks', fn ($tasks) => $tasks->pluck('title')->all() === ['Today design review', 'Tomorrow presentation'])
        ->assertViewHas('calendarEvents', fn ($events) => $events['2026-10-04']->all() === ['high'])
        ->assertViewHas('stats', fn ($stats) => $stats['Total Tasks']['value'] === 4 && $stats['Completed']['value'] === 1);
    $this->travelBack();
});

test('an empty dashboard renders without invalid progress or calendar values', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('You have no tasks scheduled for today.')
        ->assertSee('No upcoming tasks.')
        ->assertSee('--completed: 0%; --active: 0%', false)
        ->assertDontSee('NaN')
        ->assertViewHas('stats', fn ($stats) => $stats['Total Tasks']['change'] === null);
});

test('dashboard weekly changes use real task creation dates', function () {
    $this->travelTo(now()->setDate(2026, 10, 4)->startOfDay());
    $user = User::factory()->create();
    foreach ([today(), today()->subDay(), today()->subDays(8)] as $date) {
        Task::create(['user_id' => $user->id, 'title' => 'Weekly task', 'priority' => 'low', 'status' => 'pending'])->forceFill(['created_at' => $date])->save();
    }
    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertViewHas('stats', fn ($stats) => $stats['Total Tasks']['change'] === 100 && $stats['Pending']['change'] === 100 && array_sum($stats['Total Tasks']['series']) === 2);
    $this->travelBack();
});
