<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest cannot access onboarding', function () {
    $this->get(route('onboarding.show'))->assertRedirect(route('login'));
    $this->post(route('onboarding.store'))->assertRedirect(route('login'));
});

test('incomplete user can view onboarding', function () {
    $user = User::factory()->create(['name' => 'Sok Dara']);
    $user->profile()->create();

    $this->actingAs($user)
        ->get(route('onboarding.show'))
        ->assertOk()
        ->assertSee('Let’s make WorkMind yours')
        ->assertSee('Personalize Nova');
});

test('onboarding connects the user profile and Nova preferences', function () {
    $user = User::factory()->create(['name' => 'Old Name']);
    $user->profile()->create();

    $this->actingAs($user)
        ->post(route('onboarding.store'), [
            'name' => 'Sok Dara',
            'headline' => 'Laravel developer and lifelong learner',
            'bio' => 'I build useful software and want to improve my planning.',
            'city' => 'Phnom Penh',
            'country' => 'Cambodia',
            'profile_sections' => ['work', 'study', 'other'],
            'work_status' => 'employed',
            'job_title' => 'Software Developer',
            'company' => 'WorkMind Studio',
            'industry' => 'Technology',
            'study_status' => 'self_learning',
            'education_level' => 'bachelor',
            'institution' => 'RUPP',
            'field_of_study' => 'Computer Science',
            'skills' => 'Laravel, Communication, Laravel',
            'interests' => 'AI, Product design',
            'website' => 'https://example.com',
            'occupation' => 'software_it',
            'experience_level' => 'intermediate',
            'learning_interests' => ['technology', 'languages'],
            'assistance_areas' => ['technical', 'planning'],
            'other_needs' => 'Help me plan weekly learning goals.',
        ])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('success');

    $profile = $user->fresh()->profile;
    $preferences = $user->fresh()->aiPreference;

    expect($user->fresh()->name)->toBe('Sok Dara')
        ->and($profile->onboarding_completed_at)->not->toBeNull()
        ->and($profile->profile_sections)->toBe(['work', 'study', 'other'])
        ->and($profile->skills)->toBe(['Laravel', 'Communication'])
        ->and($preferences->occupation)->toBe('software_it')
        ->and($preferences->learning_interests)->toBe(['technology', 'languages'])
        ->and($preferences->assistance_areas)->toBe(['technical', 'planning']);

    $this->actingAs($user)
        ->get(route('onboarding.show'))
        ->assertRedirect(route('dashboard'));
});

test('completed users sign in directly to the dashboard', function () {
    $user = User::factory()->create(['password' => 'VerySecure!123']);
    $user->profile()->create(['onboarding_completed_at' => now()]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'VerySecure!123',
    ])->assertRedirect(route('dashboard'));
});

test('onboarding requires profile focus and Nova personalization choices', function () {
    $user = User::factory()->create();
    $user->profile()->create();

    $this->actingAs($user)
        ->post(route('onboarding.store'), [
            'name' => $user->name,
            'occupation' => 'software_it',
            'experience_level' => 'beginner',
        ])
        ->assertSessionHasErrors(['profile_sections', 'learning_interests', 'assistance_areas']);

    expect($user->profile->onboarding_completed_at)->toBeNull()
        ->and($user->aiPreference)->toBeNull();
});

test('existing account without the new-user marker skips onboarding', function () {
    $user = User::factory()->create(['password' => 'VerySecure!123']);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'VerySecure!123',
    ])->assertRedirect(route('dashboard'));

    $this->actingAs($user)
        ->get(route('onboarding.show'))
        ->assertRedirect(route('dashboard'));
});
