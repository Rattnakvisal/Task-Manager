<?php

use App\Models\AiUserPreference;
use App\Models\Task;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['services.gemini.key' => null]);
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

    $this->getJson(route('tasks.ai.preferences'))
        ->assertUnauthorized();

    $this->putJson(route('tasks.ai.preferences.update'), [])
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
            'plan_type',
            'outcome',
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

test('ai breakdown creates a learning roadmap when a user chooses a topic', function () {
    $response = $this->postJson(route('tasks.ai.breakdown'), [
        'title' => 'Laravel queues',
        'plan_type' => 'learning',
        'lang' => 'en',
    ]);

    $response->assertOk()
        ->assertJsonPath('plan_type', 'learning')
        ->assertJsonPath('suggested_category', 'Study')
        ->assertJsonStructure([
            'outcome',
            'estimated_minutes',
            'suggested_tags',
            'subtasks' => [['id', 'title', 'completed', 'estimated_minutes']],
        ]);

    expect($response->json('subtasks'))->toHaveCount(5)
        ->and($response->json('outcome'))->toContain('Laravel queues')
        ->and($response->json('subtasks.0.title'))->toContain('Laravel queues');
});

test('ai breakdown rejects an unsupported plan type', function () {
    $this->postJson(route('tasks.ai.breakdown'), [
        'title' => 'Prepare launch',
        'plan_type' => 'unknown',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('plan_type');
});

test('gemini breakdown honors the plan type selected by the user', function () {
    config([
        'services.gemini.key' => 'test-key',
        'services.gemini.model' => 'gemini-3.8-flash',
    ]);

    Http::fake([
        '*' => Http::response([
            'candidates' => [[
                'content' => ['parts' => [[
                    'text' => json_encode([
                        'plan_type' => 'task',
                        'outcome' => 'Build practical confidence with Docker.',
                        'subtasks' => [
                            ['title' => 'Define the core Docker concepts', 'estimated_minutes' => 15],
                            ['title' => 'Run a guided container example', 'estimated_minutes' => 25],
                            ['title' => 'Build a small Dockerized application', 'estimated_minutes' => 40],
                            ['title' => 'Review and explain the result', 'estimated_minutes' => 20],
                        ],
                        'suggested_priority' => 'medium',
                        'suggested_category' => 'Dev',
                        'suggested_tags' => ['docker', 'containers'],
                        'summary' => 'Practice every concept immediately.',
                    ]),
                ]]],
            ]],
        ]),
    ]);

    $response = $this->postJson(route('tasks.ai.breakdown'), [
        'title' => 'Docker fundamentals',
        'plan_type' => 'learning',
        'lang' => 'en',
    ]);

    $response->assertOk()
        ->assertJsonPath('source', 'gemini')
        ->assertJsonPath('plan_type', 'learning')
        ->assertJsonPath('suggested_category', 'Study')
        ->assertJsonPath('estimated_minutes', 100);

    Http::assertSent(fn ($request) => str_contains(data_get($request->data(), 'contents.0.parts.0.text'), 'Task title or topic: "Docker fundamentals"')
        && str_contains(data_get($request->data(), 'contents.0.parts.0.text'), 'Requested plan type: "learning"'));
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
        'message' => 'Hello Nova',
        'lang' => 'en',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'message_id',
            'created_at',
        ]);

    expect($response->json('message'))->toContain('Nova');

    $this->assertDatabaseHas('ai_chat_messages', [
        'user_id' => $this->user->id,
        'role' => 'user',
        'message' => 'Hello Nova',
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
        ->and($data['action_data']['title'])->toContain('Build payment webhook listener')
        ->and($data['action_data']['task_url'])->toStartWith('/tasks/')
        ->and($data['action_data']['source'])->toBe('nova');

    $this->assertDatabaseHas('tasks', [
        'user_id' => $this->user->id,
        'priority' => 'high',
        'category' => 'Dev',
    ]);

    expect($this->user->notifications()->count())->toBe(1)
        ->and($this->user->notifications()->first()->data['event'])->toBe('ai_task_created')
        ->and($this->user->notifications()->first()->data['title'])->toContain('Build payment webhook listener');
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

test('user can create and update an AI personalization profile', function () {
    $this->getJson(route('tasks.ai.preferences'))
        ->assertOk()
        ->assertJson([
            'success' => true,
            'configured' => false,
            'preferences' => null,
        ]);

    $payload = [
        'occupation' => 'education',
        'experience_level' => 'intermediate',
        'learning_interests' => ['languages', 'business'],
        'work_skills' => ['communication', 'writing'],
        'assistance_areas' => ['learning', 'work', 'writing_translation'],
        'other_needs' => 'Help me prepare clear lessons and improve professional English.',
    ];

    $this->putJson(route('tasks.ai.preferences.update'), $payload)
        ->assertOk()
        ->assertJsonPath('preferences.occupation', 'education')
        ->assertJsonPath('preferences.experience_level', 'intermediate')
        ->assertJsonPath('preferences.learning_interests.0', 'languages');

    $this->assertDatabaseHas('ai_user_preferences', [
        'user_id' => $this->user->id,
        'occupation' => 'education',
        'experience_level' => 'intermediate',
    ]);

    $this->getJson(route('tasks.ai.preferences'))
        ->assertOk()
        ->assertJsonPath('configured', true)
        ->assertJsonPath('preferences.other_needs', $payload['other_needs']);

    $payload['occupation'] = 'freelancer';
    $this->putJson(route('tasks.ai.preferences.update'), $payload)
        ->assertOk()
        ->assertJsonPath('preferences.occupation', 'freelancer');

    expect(AiUserPreference::where('user_id', $this->user->id)->count())->toBe(1);
});

test('AI personalization profile validates supported choices', function () {
    $this->putJson(route('tasks.ai.preferences.update'), [
        'occupation' => 'astronaut',
        'experience_level' => 'expert',
        'learning_interests' => [],
        'assistance_areas' => ['unsupported'],
    ])->assertUnprocessable()
        ->assertJsonValidationErrors([
            'occupation',
            'experience_level',
            'learning_interests',
            'assistance_areas.0',
        ]);
});

test('ai it status endpoint returns Google Gemini specs and architecture telemetry', function () {
    $response = $this->getJson(route('tasks.ai.status'));

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'provider' => 'Google Gemini',
            'model' => config('services.gemini.model', 'gemini-3.8-flash'),
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

test('gemini receives broad assistant instructions and answers IT learning questions', function () {
    config([
        'services.gemini.key' => 'test-key',
        'services.gemini.model' => 'gemini-3.8-flash',
    ]);

    Http::fake([
        '*' => Http::response([
            'candidates' => [[
                'content' => [
                    'parts' => [[
                        'text' => "## Docker networking\n\nA container network lets services communicate by name while remaining isolated.",
                    ]],
                ],
            ]],
        ]),
    ]);

    AiUserPreference::create([
        'user_id' => $this->user->id,
        'occupation' => 'student',
        'experience_level' => 'beginner',
        'learning_interests' => ['technology', 'languages'],
        'work_skills' => ['communication'],
        'assistance_areas' => ['learning', 'technical'],
        'other_needs' => 'Explain concepts with simple examples.',
    ]);

    UserProfile::create([
        'user_id' => $this->user->id,
        'headline' => 'Junior web developer and university student',
        'work_status' => 'freelancer',
        'job_title' => 'Laravel Developer',
        'study_status' => 'student',
        'education_level' => 'bachelor',
        'field_of_study' => 'Computer Science',
        'skills' => ['Laravel', 'PHP'],
        'interests' => ['Cloud computing'],
    ]);

    $response = $this->postJson(route('tasks.ai.chat'), [
        'message' => 'Teach me Docker networking as a beginner',
        'lang' => 'en',
    ]);

    $response->assertOk()
        ->assertJsonPath('message', "## Docker networking\n\nA container network lets services communicate by name while remaining isolated.");

    Http::assertSent(function ($request) {
        $payload = $request->data();
        $instructions = $payload['system_instruction']['parts'][0]['text'] ?? '';
        $contents = $payload['contents'] ?? [];

        return str_contains($request->url(), '/models/gemini-3.8-flash:generateContent')
            && str_contains($instructions, 'You are Nova')
            && str_contains($instructions, 'WorkMind application')
            && str_contains($instructions, 'IT learning')
            && str_contains($instructions, 'Work:')
            && str_contains($instructions, 'Personal:')
            && str_contains($instructions, '"occupation":"student"')
            && str_contains($instructions, 'Explain concepts with simple examples.')
            && str_contains($instructions, 'WorkMind User Profile')
            && str_contains($instructions, '"job_title":"Laravel Developer"')
            && str_contains($instructions, '"field_of_study":"Computer Science"')
            && str_contains($instructions, '"skills":["Laravel","PHP"]')
            && data_get($contents, '0.parts.0.text') === 'Teach me Docker networking as a beginner';
    });
});

test('gemini can create a task from natural conversation using function calling', function () {
    config([
        'services.gemini.key' => 'test-key',
        'services.gemini.model' => 'gemini-3.8-flash',
    ]);

    Http::fake([
        '*' => Http::response([
            'candidates' => [[
                'content' => [
                    'parts' => [[
                        'functionCall' => [
                            'name' => 'create_task',
                            'args' => [
                                'title' => 'Study Laravel queues',
                                'priority' => 'high',
                                'category' => 'Study',
                                'description' => 'Review workers, retries, and failed jobs.',
                            ],
                        ],
                    ]],
                ],
            ]],
        ]),
    ]);

    $response = $this->postJson(route('tasks.ai.chat'), [
        'message' => 'Please remember that I need to study Laravel queues',
        'lang' => 'en',
    ]);

    $response->assertOk()
        ->assertJsonPath('action_type', 'task_created')
        ->assertJsonPath('action_data.title', 'Study Laravel queues');

    $this->assertDatabaseHas('tasks', [
        'user_id' => $this->user->id,
        'title' => 'Study Laravel queues',
        'priority' => 'high',
        'category' => 'Study',
    ]);
});
