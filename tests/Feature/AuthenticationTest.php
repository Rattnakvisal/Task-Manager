<?php

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('guests can view authentication forms', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Sign in')
        ->assertSee('Create account')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Cache-Control', 'no-store, private');
    $this->get(route('register'))->assertOk()->assertSee('Create account')->assertSee('Sign in');
});

test('email addresses are normalized before authentication', function () {
    $user = User::factory()->create([
        'email' => 'alex@example.com',
        'password' => 'VerySecure!123',
    ]);

    $this->post(route('login.store'), [
        'email' => '  ALEX@EXAMPLE.COM  ',
        'password' => 'VerySecure!123',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

test('authenticated pages prevent sensitive response caching', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
});

test('a user can register with a strong password', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Alex Morgan',
        'email' => 'alex@example.com',
        'password' => 'VerySecure!123',
        'password_confirmation' => 'VerySecure!123',
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticated();
    expect(Hash::check('VerySecure!123', User::first()->password))->toBeTrue();
});

test('weak passwords are rejected', function () {
    $this->post(route('register.store'), [
        'name' => 'Alex Morgan',
        'email' => 'alex@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('password');

    $this->assertGuest();
});

test('a user can sign in and sign out', function () {
    $user = User::factory()->create(['password' => 'VerySecure!123']);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'VerySecure!123',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
    $this->post(route('logout'))->assertRedirect(route('login'));
    $this->assertGuest();
});

test('users cannot access another users task', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $task = Task::create([
        'user_id' => $owner->id,
        'title' => 'Private task',
        'priority' => 'medium',
        'status' => 'pending',
    ]);

    $this->actingAs($intruder)->get(route('tasks.edit', $task))->assertNotFound();
    $this->actingAs($intruder)->put(route('tasks.update', $task), [
        'title' => 'Changed',
        'priority' => 'high',
        'status' => 'completed',
    ])->assertNotFound();

    expect($task->fresh()->title)->not->toBe('Changed');
});
