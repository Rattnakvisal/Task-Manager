<?php

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

uses(RefreshDatabase::class);

function mobileToken(User $user): string
{
    return $user->createToken('Android test', ['*'], now()->addDays(30))->plainTextToken;
}

it('requires authentication to update a mobile profile', function () {
    $this->patchJson('/api/mobile/profile', ['name' => 'New Name'])->assertUnauthorized();
});

it('updates only the signed-in mobile users name', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $this->withToken(mobileToken($user))->patchJson('/api/mobile/profile', [
        'id' => $other->id,
        'name' => '  Updated Name  ',
        'email' => 'changed@example.com',
        'password' => 'changed',
    ])->assertOk()->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('user.name', 'Updated Name')
        ->assertJsonPath('user.email', $user->email)
        ->assertJsonMissingPath('user.password');
    expect($user->fresh()->name)->toBe('Updated Name');
    expect($user->fresh()->password)->toBe($user->password);
    expect($other->fresh()->name)->toBe($other->name);
});

it('validates the mobile profile name before saving', function () {
    $user = User::factory()->create();
    $this->withToken(mobileToken($user))->patchJson('/api/mobile/profile', ['name' => '   '])
        ->assertUnprocessable()->assertJsonValidationErrors('name');
    $this->patchJson('/api/mobile/profile', ['name' => str_repeat('x', 101)])
        ->assertUnprocessable()->assertJsonValidationErrors('name');
    expect($user->fresh()->name)->toBe($user->name);
});

it('registers a mobile account with a hashed expiring token and normalized email', function () {
    $response = $this->postJson('/api/mobile/register', [
        'name' => 'Android User', 'email' => ' Android@Example.com ',
        'password' => 'SecurePass1!', 'password_confirmation' => 'SecurePass1!',
        'device_name' => 'Pixel',
    ])->assertCreated()->assertJsonPath('user.email', 'android@example.com');
    $user = User::firstOrFail();
    expect(Hash::check('SecurePass1!', $user->password))->toBeTrue();
    expect($user->profile->onboarding_completed_at)->not->toBeNull();
    $token = PersonalAccessToken::firstOrFail();
    expect($token->token)->not->toBe($response->json('token'));
    expect($token->expires_at->isFuture())->toBeTrue();
    $this->withToken($response->json('token'))->getJson('/api/mobile/me')
        ->assertOk()->assertJsonPath('user.id', $user->id)->assertJsonMissingPath('user.password');
});

it('uses existing web accounts and rejects incorrect credentials', function () {
    $user = User::factory()->create(['password' => 'SecurePass1!']);
    $this->postJson('/api/mobile/login', [
        'email' => $user->email, 'password' => 'wrong', 'device_name' => 'Pixel',
    ])->assertUnprocessable()->assertJsonValidationErrors('email');
    $this->postJson('/api/mobile/login', [
        'email' => strtoupper($user->email), 'password' => 'SecurePass1!', 'device_name' => 'Pixel',
    ])->assertOk()->assertJsonStructure(['token', 'expires_at', 'user']);
});

it('requires a valid unexpired bearer token', function () {
    $this->getJson('/api/mobile/tasks')->assertUnauthorized();
    $this->withToken('invalid')->getJson('/api/mobile/me')->assertUnauthorized();
    $user = User::factory()->create();
    $expired = $user->createToken('Expired', ['*'], now()->subMinute())->plainTextToken;
    $this->withToken($expired)->getJson('/api/mobile/tasks')->assertUnauthorized();
});

it('creates updates and deletes only the authenticated users tasks', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $foreign = Task::create(['user_id' => $other->id, 'title' => 'Private', 'priority' => 'low', 'status' => 'pending']);
    $this->withToken(mobileToken($user));
    $payload = [
        'title' => 'Plan Android UI', 'priority' => 'high', 'status' => 'pending',
        'due_date' => '2026-11-01', 'end_date' => '2026-11-02',
        'subtasks' => [['id' => 'mobile-1', 'title' => 'Sketch screens', 'completed' => false]],
        'tags' => ['mobile'], 'is_pinned' => true, 'user_id' => $other->id,
    ];
    $id = $this->postJson('/api/mobile/tasks', $payload)->assertCreated()
        ->assertJsonPath('task.subtasks_count', 1)->json('task.id');
    $this->assertDatabaseHas('tasks', ['id' => $id, 'user_id' => $user->id]);
    $this->getJson('/api/mobile/tasks')->assertOk()->assertJsonCount(1, 'tasks');
    $this->getJson('/api/mobile/dashboard')->assertOk()->assertJsonPath('stats.total', 1);
    $this->getJson('/api/mobile/calendar')->assertOk()->assertJsonCount(1, 'tasks');
    $this->getJson('/api/mobile/notifications')->assertOk();
    $payload['status'] = 'completed';
    $payload['subtasks'][0]['completed'] = true;
    $this->putJson("/api/mobile/tasks/{$id}", $payload)->assertOk()
        ->assertJsonPath('task.status', 'completed')->assertJsonPath('task.completed_subtasks_count', 1);
    $this->getJson("/api/mobile/tasks/{$foreign->id}")->assertNotFound();
    $this->putJson("/api/mobile/tasks/{$foreign->id}", $payload)->assertNotFound();
    $this->deleteJson("/api/mobile/tasks/{$foreign->id}")->assertNotFound();
    $this->deleteJson("/api/mobile/tasks/{$id}")->assertOk();
    $this->assertDatabaseMissing('tasks', ['id' => $id]);
    $this->assertDatabaseHas('tasks', ['id' => $foreign->id]);
});

it('validates task input and account password confirmation', function () {
    $this->postJson('/api/mobile/register', [
        'name' => 'Test', 'email' => 'test@example.com', 'password' => 'weak',
        'password_confirmation' => 'different', 'device_name' => 'Pixel',
    ])->assertUnprocessable()->assertJsonValidationErrors('password');
    $this->withToken(mobileToken(User::factory()->create()))->postJson('/api/mobile/tasks', [
        'title' => '', 'priority' => 'urgent', 'status' => 'unknown',
    ])->assertUnprocessable()->assertJsonValidationErrors(['title', 'priority', 'status']);
});

it('revokes only the current device token on logout', function () {
    $user = User::factory()->create();
    $current = mobileToken($user);
    $other = mobileToken($user);
    $this->withToken($current)->postJson('/api/mobile/logout')->assertOk();
    // Forget the guard cached during this test to simulate a new HTTP request.
    app('auth')->forgetGuards();
    $this->withToken($current)->getJson('/api/mobile/me')->assertUnauthorized();
    app('auth')->forgetGuards();
    $this->withToken($other)->getJson('/api/mobile/me')->assertOk();
    expect($user->tokens()->count())->toBe(1);
});

it('throttles repeated login attempts', function () {
    $payload = ['email' => 'missing@example.com', 'password' => 'wrong', 'device_name' => 'Pixel'];
    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/mobile/login', $payload)->assertUnprocessable();
    }
    $this->postJson('/api/mobile/login', $payload)->assertTooManyRequests();
});
