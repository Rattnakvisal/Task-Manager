<?php

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated pages render sweetalert flash containers and logout confirmation', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['success' => 'Custom SweetAlert success test!'])
        ->get(route('tasks.index'));

    $response->assertOk();
    $response->assertSee('id="flash-session-success"', false);
    $response->assertSee('data-message="Custom SweetAlert success test!"', false);
    $response->assertSee('data-confirm-logout', false);
});

test('validation errors populate sweetalert error container', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from(route('tasks.create'))
        ->followingRedirects()
        ->post(route('tasks.store'), [
            'title' => '',
            'priority' => 'medium',
            'status' => 'pending',
        ]);

    $response->assertOk();
    $response->assertSee('id="flash-session-errors"', false);
    $response->assertSee('The title field is required.', false);
});

test('task delete form renders confirmation attribute', function () {
    $user = User::factory()->create();
    $task = Task::create([
        'user_id' => $user->id,
        'title' => 'Delete SweetAlert Test Task',
        'priority' => 'high',
        'status' => 'pending',
    ]);

    $response = $this->actingAs($user)->get(route('tasks.index'));

    $response->assertOk();
    $response->assertSee('data-confirm="Delete this task?"', false);
    $response->assertSee(route('tasks.destroy', $task), false);
});

test('pages render task modal triggers and novalidate forms', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('tasks.index'));
    $response->assertOk();
    $response->assertSee('data-open-task-modal', false);
    $response->assertSee('id="task-modal"', false);
    $response->assertSee('data-task-form novalidate', false);

    $createPage = $this->actingAs($user)->get(route('tasks.create'));
    $createPage->assertOk();
    $createPage->assertSee('data-task-form novalidate', false);
    $createPage->assertSee('data-open-task-modal', false);
});

test('dashboard renders modal triggers for new tasks', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));
    $response->assertOk();
    $response->assertSee('data-open-task-modal', false);
});

