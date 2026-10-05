<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests cannot access or update a profile', function () {
    $this->get(route('profile.edit'))->assertRedirect(route('login'));
    $this->put(route('profile.update'), ['name' => 'Guest'])->assertRedirect(route('login'));
});

test('authenticated user can view the connected profile sections without a phone field', function () {
    $user = User::factory()->create(['name' => 'Sok Dara']);

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Your personal workspace identity')
        ->assertSee('Personal information')
        ->assertSee('Work information')
        ->assertSee('Study information')
        ->assertSee('Skills and other information')
        ->assertSee('My Profile')
        ->assertSee('Glossy Blue Checklist App Icon.png')
        ->assertDontSee('name="phone"', false);
});

test('authenticated user can save personal work study and other information', function () {
    $user = User::factory()->create(['name' => 'Old Name']);

    $this->actingAs($user)
        ->put(route('profile.update'), [
            'section' => 'personal',
            'name' => 'Sok Dara',
            'headline' => 'Product designer and lifelong learner',
            'bio' => 'I design useful digital products and study business.',
            'city' => 'Phnom Penh',
            'country' => 'Cambodia',
            'profile_sections' => ['work', 'study', 'other'],
        ])
        ->assertRedirect(route('profile.edit').'#personal')
        ->assertSessionHas('success');

    $this->actingAs($user)
        ->put(route('profile.update'), [
            'section' => 'work',
            'work_status' => 'employed',
            'job_title' => 'Product Designer',
            'company' => 'WorkMind Studio',
            'industry' => 'Technology',
        ])
        ->assertRedirect(route('profile.edit').'#work')
        ->assertSessionHas('success');

    $this->actingAs($user)
        ->put(route('profile.update'), [
            'section' => 'study',
            'study_status' => 'self_learning',
            'education_level' => 'bachelor',
            'institution' => 'Royal University of Phnom Penh',
            'field_of_study' => 'Business Administration',
        ])
        ->assertRedirect(route('profile.edit').'#study')
        ->assertSessionHas('success');

    $this->actingAs($user)
        ->put(route('profile.update'), [
            'section' => 'other',
            'skills' => "Figma, Communication, Figma\nProduct strategy",
            'interests' => 'AI, Business, Personal growth',
            'website' => 'https://example.com/portfolio',
        ])
        ->assertRedirect(route('profile.edit').'#other')
        ->assertSessionHas('success');

    expect($user->fresh()->name)->toBe('Sok Dara')
        ->and($user->profile()->first()->skills)->toBe(['Figma', 'Communication', 'Product strategy'])
        ->and($user->profile()->first()->interests)->toBe(['AI', 'Business', 'Personal growth']);

    $this->assertDatabaseHas('user_profiles', [
        'user_id' => $user->id,
        'job_title' => 'Product Designer',
        'field_of_study' => 'Business Administration',
        'website' => 'https://example.com/portfolio',
    ]);

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Product Designer')
        ->assertSee('Business Administration')
        ->assertSee('Figma, Communication, Product strategy');
});

test('saving one profile section does not change information in other sections', function () {
    $user = User::factory()->create();
    $user->profile()->create([
        'profile_sections' => ['work', 'study', 'other'],
        'study_status' => 'student',
        'field_of_study' => 'Computer Science',
        'skills' => ['PHP'],
    ]);

    $this->actingAs($user)
        ->put(route('profile.update'), [
            'section' => 'work',
            'work_status' => 'employed',
            'job_title' => 'Developer',
            'study_status' => 'graduated',
            'field_of_study' => 'Should be ignored',
            'skills' => 'Should be ignored',
        ])
        ->assertRedirect(route('profile.edit').'#work');

    $profile = $user->profile()->first();
    expect($profile->job_title)->toBe('Developer')
        ->and($profile->study_status)->toBe('student')
        ->and($profile->field_of_study)->toBe('Computer Science')
        ->and($profile->skills)->toBe(['PHP']);
});

test('profile validates only the selected section', function () {
    $user = User::factory()->create();
    $user->profile()->create(['profile_sections' => ['work', 'study', 'other']]);

    $this->actingAs($user)
        ->from(route('profile.edit').'#work')
        ->put(route('profile.update'), [
            'section' => 'work',
            'work_status' => 'unknown',
            'study_status' => 'unknown-but-not-selected',
        ])
        ->assertRedirect(route('profile.edit').'#work')
        ->assertSessionHasErrors(['work_status'])
        ->assertSessionDoesntHaveErrors(['study_status']);

    $this->actingAs($user)
        ->put(route('profile.update'), [
            'section' => 'study',
            'study_status' => 'unknown',
            'education_level' => 'unknown',
        ])
        ->assertRedirect(route('profile.edit').'#study')
        ->assertSessionHasErrors(['study_status', 'education_level']);

    $this->actingAs($user)
        ->put(route('profile.update'), [
            'section' => 'other',
            'website' => 'not-a-url',
        ])
        ->assertRedirect(route('profile.edit').'#other')
        ->assertSessionHasErrors(['website']);

    $this->assertDatabaseHas('user_profiles', [
        'user_id' => $user->id,
        'work_status' => null,
        'study_status' => null,
        'website' => null,
    ]);
});

test('users can choose multiple profile areas and unselected tabs stay hidden', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('profile.update'), [
            'section' => 'personal',
            'name' => $user->name,
            'profile_sections' => ['work', 'study'],
        ])
        ->assertRedirect(route('profile.edit').'#personal');

    expect($user->profile()->first()->profile_sections)->toBe(['work', 'study']);

    $response = $this->actingAs($user)->get(route('profile.edit'));
    $response->assertOk()
        ->assertSee('name="profile_sections[]" value="work"', false)
        ->assertSee('name="profile_sections[]" value="study"', false)
        ->assertSee('data-profile-tab="other"', false);

    expect($response->getContent())->toMatch('/data-profile-tab="work"[^>]+class="profile-tab inline-flex/')
        ->toMatch('/data-profile-tab="study"[^>]+class="profile-tab inline-flex/')
        ->toMatch('/data-profile-tab="other"[^>]+class="profile-tab hidden/');
});
