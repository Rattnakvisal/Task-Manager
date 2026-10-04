<?php

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('guest cannot access AI copilot endpoints', function () {
    auth()->logout();

    $this->postJson(route('tasks.ai.breakdown'), ['title' => 'Test task'])
        ->assertUnauthorized();

    $this->postJson(route('tasks.ai.enhance'), ['title' => 'Test task'])
        ->assertUnauthorized();

    $this->postJson(route('tasks.ai.parse-nlp'), ['text' => 'Test task'])
        ->assertUnauthorized();

    $this->getJson(route('tasks.ai.standup-brief'))
        ->assertUnauthorized();
});

test('ai breakdown endpoint generates subtasks for a given task title', function () {
    $response = $this->postJson(route('tasks.ai.breakdown'), [
        'title' => 'Build customer checkout stripe payment gateway',
        'description' => 'Support credit cards and webhooks.',
        'category' => 'Dev',
        'lang' => 'en',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'subtasks',
            'suggested_priority',
            'suggested_category',
            'estimated_minutes',
            'summary',
        ]);

    $data = $response->json();
    expect($data['success'])->toBeTrue()
        ->and($data['subtasks'])->not->toBeEmpty()
        ->and($data['subtasks'][0])->toHaveKeys(['id', 'title', 'completed']);
});

test('ai breakdown endpoint works in Khmer language', function () {
    $response = $this->postJson(route('tasks.ai.breakdown'), [
        'title' => 'កែកំហុស Authentication និង Login',
        'lang' => 'km',
    ]);

    $response->assertOk();
    $data = $response->json();
    expect($data['success'])->toBeTrue()
        ->and($data['subtasks'])->not->toBeEmpty();
});

test('ai breakdown can be applied directly to an existing task', function () {
    $task = Task::create([
        'user_id' => $this->user->id,
        'title' => 'Redesign user settings profile page',
        'priority' => 'medium',
        'status' => 'pending',
    ]);

    $response = $this->postJson(route('tasks.ai.breakdown-existing', $task), [
        'lang' => 'en',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'task',
            'subtasks',
            'subtasks_count',
            'subtasks_progress',
        ]);

    $fresh = $task->fresh();
    expect($fresh->subtasks_count)->toBeGreaterThan(0)
        ->and($fresh->category)->toBe('Design');
});

test('user cannot apply ai breakdown to another users task', function () {
    $otherUser = User::factory()->create();
    $task = Task::create([
        'user_id' => $otherUser->id,
        'title' => 'Secret other user task',
        'priority' => 'medium',
        'status' => 'pending',
    ]);

    $this->postJson(route('tasks.ai.breakdown-existing', $task))
        ->assertNotFound();
});

test('ai enhance endpoint polishes task title and description', function () {
    $response = $this->postJson(route('tasks.ai.enhance'), [
        'title' => 'fix auth bug',
        'lang' => 'en',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'title',
            'description',
            'suggested_category',
            'suggested_priority',
        ]);

    $data = $response->json();
    expect($data['title'])->toBe('Fix auth bug')
        ->and($data['suggested_category'])->toBe('Dev')
        ->and($data['suggested_priority'])->toBe('high');
});

test('ai nlp parser correctly extracts date, priority, and hashtags', function () {
    $response = $this->postJson(route('tasks.ai.parse-nlp'), [
        'text' => 'Fix checkout bug by tomorrow priority high #Dev',
    ]);

    $response->assertOk();
    $parsed = $response->json('parsed');

    expect($parsed['priority'])->toBe('high')
        ->and($parsed['category'])->toBe('Dev')
        ->and($parsed['due_date'])->not->toBeNull()
        ->and($parsed['title'])->toContain('Fix checkout bug');
});

test('ai standup brief summarizes user workload and recommends focus', function () {
    Task::create([
        'user_id' => $this->user->id,
        'title' => 'Critical production database backup',
        'priority' => 'high',
        'status' => 'in_progress',
        'due_date' => now()->toDateString(),
    ]);

    $response = $this->getJson(route('tasks.ai.standup-brief', ['lang' => 'en']));

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'brief' => [
                'headline',
                'summary',
                'focus_task',
                'overdue_count',
                'urgent_count',
                'action_recommendation',
            ],
        ]);

    $brief = $response->json('brief');
    expect($brief['focus_task']['title'])->toBe('Critical production database backup');
});

test('ai chat endpoint handles user messages, stores history, and replies', function () {
    $response = $this->postJson(route('tasks.ai.chat'), [
        'message' => 'Hello AI, how can you help me today?',
        'lang' => 'en',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'message_id',
            'created_at',
        ]);

    $this->assertDatabaseHas('ai_chat_messages', [
        'user_id' => $this->user->id,
        'role' => 'user',
        'message' => 'Hello AI, how can you help me today?',
    ]);

    $this->assertDatabaseHas('ai_chat_messages', [
        'user_id' => $this->user->id,
        'role' => 'assistant',
    ]);
});

test('ai chat can create a task directly from conversation intent', function () {
    $response = $this->postJson(route('tasks.ai.chat'), [
        'message' => 'create task: Build payment webhook listener #Dev !high by tomorrow',
        'lang' => 'en',
    ]);

    $response->assertOk();
    $data = $response->json();

    expect($data['action_type'])->toBe('task_created')
        ->and($data['action_data'])->not->toBeNull()
        ->and($data['action_data']['title'])->toContain('Build payment webhook listener');

    $this->assertDatabaseHas('tasks', [
        'user_id' => $this->user->id,
        'priority' => 'high',
        'category' => 'Dev',
    ]);
});

test('ai chat history can be fetched and cleared', function () {
    $this->postJson(route('tasks.ai.chat'), [
        'message' => 'First message',
    ]);

    $historyResponse = $this->getJson(route('tasks.ai.chat.history'));
    $historyResponse->assertOk()
        ->assertJsonStructure(['success', 'history']);
    expect($historyResponse->json('history'))->toHaveCount(2); // user + assistant

    $clearResponse = $this->postJson(route('tasks.ai.chat.clear'));
    $clearResponse->assertOk();

    $this->assertDatabaseEmpty('ai_chat_messages');
});

test('ai it status endpoint returns Google Gemini specs and architecture telemetry', function () {
    $response = $this->getJson(route('tasks.ai.status'));

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'provider' => 'Google Gemini',
            'model' => 'gemini-2.5-flash',
            'context_window' => '1,048,576 tokens',
        ])
        ->assertJsonStructure([
            'success',
            'provider',
            'model',
            'status',
            'context_window',
            'backend',
            'database',
            'capabilities',
        ]);
});

test('ai copilot handles IT engineering requests and DevOps tags', function () {
    // 1. NLP parsing with #DevOps tag
    $nlpResponse = $this->postJson(route('tasks.ai.parse-nlp'), [
        'text' => 'Deploy Redis queue worker and Docker container #DevOps !high by Friday',
    ]);

    $nlpResponse->assertOk();
    $parsed = $nlpResponse->json('parsed');
    expect($parsed['category'])->toBe('Dev')
        ->and($parsed['priority'])->toBe('high')
        ->and($parsed['tags'])->toContain('devops');

    // 2. Chat with IT engineering query
    $chatResponse = $this->postJson(route('tasks.ai.chat'), [
        'message' => 'Tell me the IT specs of Gemini Copilot and how to dockerize Laravel',
    ]);

    $chatResponse->assertOk();
    $data = $chatResponse->json();
    expect($data['success'])->toBeTrue()
        ->and($data['message'])->toContain('Google Gemini');
});


