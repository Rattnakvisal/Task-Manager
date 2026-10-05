<?php

use App\Models\Task;
use App\Models\User;
use App\Notifications\AiTaskCreatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest cannot access persistent task alerts', function () {
    $this->getJson(route('api.task-alerts.index'))->assertUnauthorized();
    $this->patchJson(route('api.task-alerts.read-all'))->assertUnauthorized();
});

test('user can fetch and mark a Nova task alert as read', function () {
    $user = User::factory()->create();
    $task = Task::create([
        'user_id' => $user->id,
        'title' => 'Prepare AI project demo',
        'priority' => 'high',
        'category' => 'Work',
        'status' => 'pending',
    ]);
    $user->notify(new AiTaskCreatedNotification($task));
    $notification = $user->notifications()->first();

    $this->actingAs($user)
        ->getJson(route('api.task-alerts.index'))
        ->assertOk()
        ->assertJsonPath('unread_count', 1)
        ->assertJsonPath('notifications.0.notification_id', $notification->id)
        ->assertJsonPath('notifications.0.task_id', $task->id)
        ->assertJsonPath('notifications.0.title', 'Prepare AI project demo')
        ->assertJsonPath('notifications.0.task_url', route('tasks.show', $task, false))
        ->assertJsonPath('notifications.0.read', false);

    $this->actingAs($user)
        ->patchJson(route('api.task-alerts.read', $notification->id))
        ->assertOk()
        ->assertJson(['success' => true]);

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('user can mark all Nova task alerts as read without changing another users alerts', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    foreach ([$user, $user, $otherUser] as $index => $owner) {
        $task = Task::create([
            'user_id' => $owner->id,
            'title' => 'Generated task '.($index + 1),
            'priority' => 'medium',
            'category' => 'Work',
            'status' => 'pending',
        ]);
        $owner->notify(new AiTaskCreatedNotification($task));
    }

    $this->actingAs($user)
        ->patchJson(route('api.task-alerts.read-all'))
        ->assertOk();

    expect($user->fresh()->unreadNotifications()->count())->toBe(0)
        ->and($otherUser->fresh()->unreadNotifications()->count())->toBe(1);
});

test('user cannot mark another users alert as read', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $task = Task::create([
        'user_id' => $otherUser->id,
        'title' => 'Private generated task',
        'priority' => 'medium',
        'category' => 'Personal',
        'status' => 'pending',
    ]);
    $otherUser->notify(new AiTaskCreatedNotification($task));

    $this->actingAs($user)
        ->patchJson(route('api.task-alerts.read', $otherUser->notifications()->first()->id))
        ->assertNotFound();
});
