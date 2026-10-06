<?php

use App\Models\AiChatMessage;
use App\Models\AiUserPreference;
use App\Models\Task;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\AiTaskCopilotService;
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

test('fallback creates a specific Laravel Supabase PostgreSQL setup plan', function () {
    $response = $this->postJson(route('tasks.ai.breakdown'), [
        'title' => 'Superbase PostgreSQL',
        'lang' => 'en',
    ]);

    $response->assertOk()
        ->assertJsonPath('source', 'smart_heuristic')
        ->assertJsonPath('suggested_category', 'Dev')
        ->assertJsonPath('subtasks.0.title', 'Create the Supabase project and store the database password securely');

    expect($response->json('subtasks'))->toHaveCount(5)
        ->and($response->json('subtasks.1.title'))->toContain('Session pooler')
        ->and($response->json('subtasks.1.title'))->toContain('5432')
        ->and($response->json('subtasks.2.title'))->toContain('DB_CONNECTION=pgsql')
        ->and($response->json('summary'))->toContain('6543');
});

test('NLP parser corrects common Supabase and PostgreSQL product-name typos', function () {
    $response = $this->postJson(route('tasks.ai.parse-nlp'), [
        'text' => 'setup Superbase postgresql',
    ]);

    $response->assertOk()
        ->assertJsonPath('parsed.title', 'setup Supabase PostgreSQL')
        ->assertJsonPath('parsed.category', 'Dev');
});

test('fallback creates domain-specific plans across different skills', function (string $title, string $category, string $expectedStepText) {
    $response = $this->postJson(route('tasks.ai.breakdown'), [
        'title' => $title,
        'lang' => 'en',
    ]);

    $response->assertOk()
        ->assertJsonPath('source', 'smart_heuristic')
        ->assertJsonPath('suggested_category', $category);

    expect($response->json('subtasks'))->toHaveCount(5)
        ->and(collect($response->json('subtasks'))->pluck('title')->implode(' '))->toContain($expectedStepText);
})->with([
    'cybersecurity' => ['Perform an OWASP security review', 'Dev', 'threat model'],
    'devops' => ['Deploy Docker services to production', 'Dev', 'rollback'],
    'networking' => ['Diagnose DNS network latency', 'Dev', 'network path'],
    'data analytics' => ['Analyze customer dataset and build a dashboard', 'Design', 'business question'],
    'writing' => ['Write a customer onboarding proposal', 'Work', 'audience'],
    'marketing' => ['Launch a social media marketing campaign', 'Work', 'measurable objective'],
    'career' => ['Prepare for a software engineering job interview', 'Personal', 'target role'],
    'wellness' => ['Create a sustainable fitness habit', 'Personal', 'baseline'],
    'business operations' => ['Improve the customer support workflow', 'Work', 'business outcome'],
    'event planning' => ['Plan a family wedding event', 'Personal', 'budget'],
]);

test('fallback keeps a simple one-action task concise', function () {
    $response = $this->postJson(route('tasks.ai.breakdown'), [
        'title' => 'Call the landlord',
        'lang' => 'en',
    ]);

    $response->assertOk();

    expect($response->json('subtasks'))->toHaveCount(3)
        ->and($response->json('summary'))->toContain('without unnecessary filler');
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

test('applying the same ai breakdown twice does not duplicate checklist steps', function () {
    $task = Task::create([
        'user_id' => $this->user->id,
        'title' => 'Build Laravel API authentication',
        'priority' => 'medium',
        'status' => 'pending',
    ]);

    $first = $this->postJson(route('tasks.ai.breakdown-existing', $task), [
        'lang' => 'en',
        'plan_type' => 'project',
    ])->assertOk();
    $firstCount = $first->json('subtasks_count');

    $this->postJson(route('tasks.ai.breakdown-existing', $task), [
        'lang' => 'en',
        'plan_type' => 'project',
    ])->assertOk()
        ->assertJsonPath('added_subtasks_count', 0)
        ->assertJsonPath('subtasks_count', $firstCount);

    expect($task->fresh()->subtasks_count)->toBe($firstCount);
});

test('existing task breakdown and standup validate language and plan type', function () {
    $task = Task::create([
        'user_id' => $this->user->id,
        'title' => 'Validate AI inputs',
        'status' => 'pending',
    ]);

    $this->postJson(route('tasks.ai.breakdown-existing', $task), [
        'lang' => 'fr',
        'plan_type' => 'unsupported',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['lang', 'plan_type']);

    $this->getJson(route('tasks.ai.standup-brief', ['lang' => 'fr']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('lang');
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

test('ai chat previews a task draft before an idempotent confirmation', function () {
    $response = $this->postJson(route('tasks.ai.chat'), [
        'message' => 'create task: Build payment webhook listener #Dev !high by tomorrow',
        'lang' => 'en',
    ]);

    $response->assertOk();
    $data = $response->json();

    expect($data['action_type'])->toBe('task_draft')
        ->and($data['action_data'])->not->toBeNull()
        ->and($data['action_data']['title'])->toContain('Build payment webhook listener')
        ->and($data['action_data']['subtasks_count'])->toBeGreaterThanOrEqual(4)
        ->and($data['action_data']['subtasks'])->toHaveCount($data['action_data']['subtasks_count'])
        ->and($data['action_data']['draft_id'])->not->toBeEmpty()
        ->and($data['action_data']['source'])->toBe('nova');

    $this->assertDatabaseMissing('tasks', [
        'user_id' => $this->user->id,
        'priority' => 'high',
        'category' => 'Dev',
    ]);

    $confirm = $this->postJson(route('tasks.ai.drafts.confirm', $data['action_data']['draft_id']));
    $confirm->assertOk()
        ->assertJsonPath('action_type', 'task_created')
        ->assertJsonPath('action_data.title', $data['action_data']['title']);

    // A retry returns the same task rather than creating a duplicate.
    $retry = $this->postJson(route('tasks.ai.drafts.confirm', $data['action_data']['draft_id']));
    $retry->assertOk()
        ->assertJsonPath('action_data.task_id', $confirm->json('action_data.task_id'));

    $this->assertDatabaseHas('tasks', [
        'user_id' => $this->user->id,
        'priority' => 'high',
        'category' => 'Dev',
    ]);

    $createdTask = Task::where('user_id', $this->user->id)->latest('id')->firstOrFail();
    expect($createdTask->subtasks)->toHaveCount($data['action_data']['subtasks_count'])
        ->and($createdTask->subtasks[0])->toHaveKeys(['id', 'title', 'completed', 'estimated_minutes']);

    expect($this->user->notifications()->count())->toBe(1)
        ->and($this->user->notifications()->first()->data['event'])->toBe('ai_task_created')
        ->and($this->user->notifications()->first()->data['title'])->toContain('Build payment webhook listener');

    expect(Task::where('user_id', $this->user->id)->count())->toBe(1);
});

test('a recent AI-created task can be undone by its owner', function () {
    $draft = $this->postJson(route('tasks.ai.chat'), [
        'message' => 'create task: Review onboarding checklist #Work',
        'lang' => 'en',
    ])->assertOk();

    $confirmed = $this->postJson(route('tasks.ai.drafts.confirm', $draft->json('action_data.draft_id')))
        ->assertOk();

    $taskId = $confirmed->json('action_data.task_id');
    $this->deleteJson($confirmed->json('action_data.undo_url'))
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->assertDatabaseMissing('tasks', ['id' => $taskId]);
    expect($this->user->notifications()->count())->toBe(0);
});

test('an expired AI draft cannot create a task', function () {
    $draft = $this->postJson(route('tasks.ai.chat'), [
        'message' => 'create task: Prepare expired draft test',
        'lang' => 'en',
    ])->assertOk();

    $this->travel(16)->minutes();

    $this->postJson(route('tasks.ai.drafts.confirm', $draft->json('action_data.draft_id')))
        ->assertGone()
        ->assertJsonPath('success', false);

    $this->assertDatabaseMissing('tasks', ['title' => 'Prepare expired draft test']);
});

test('AI analyze returns a reviewable form draft without creating a task', function () {
    $response = $this->postJson(route('tasks.ai.analyze'), [
        'title' => 'Learn queue workers',
        'description' => 'Understand retries and failed jobs.',
        'category' => 'Study',
        'plan_type' => 'learning',
        'lang' => 'en',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'title',
            'description',
            'subtasks',
            'suggested_category',
            'suggested_priority',
            'source',
        ]);

    expect($response->json('subtasks'))->not->toBeEmpty();
    $this->assertDatabaseEmpty('tasks');
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

    expect($response->json('backend'))->toStartWith('Laravel '.app()->version())
        ->and($response->json('status'))->toBe('fallback_only');
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
        ->assertJsonPath('action_type', 'task_draft')
        ->assertJsonPath('action_data.title', 'Study Laravel queues');

    $this->assertDatabaseMissing('tasks', ['title' => 'Study Laravel queues']);
    $this->postJson(route('tasks.ai.drafts.confirm', $response->json('action_data.draft_id')))
        ->assertOk()
        ->assertJsonPath('action_type', 'task_created');

    $this->assertDatabaseHas('tasks', [
        'user_id' => $this->user->id,
        'title' => 'Study Laravel queues',
        'priority' => 'high',
        'category' => 'Study',
    ]);
});

test('gemini api calls transmit api key via secure x-goog-api-key header and sanitize prompt quotes', function () {
    config([
        'services.gemini.key' => 'secret-test-key-12345',
        'services.gemini.model' => 'gemini-3.8-flash',
    ]);

    Http::fake([
        '*' => Http::response([
            'candidates' => [[
                'content' => ['parts' => [[
                    'text' => json_encode([
                        'title' => 'Polished Task Title',
                        'description' => 'Secure description',
                        'suggested_category' => 'Dev',
                        'suggested_priority' => 'high',
                        'suggested_tags' => ['security'],
                    ]),
                ]]],
            ]],
        ]),
    ]);

    $response = $this->postJson(route('tasks.ai.enhance'), [
        'title' => 'Fix "critical" SQL vulnerability',
        'description' => "Test description\r\nline two",
        'lang' => 'en',
    ]);

    $response->assertOk();

    Http::assertSent(function ($request) {
        $hasSecureHeader = $request->hasHeader('x-goog-api-key', 'secret-test-key-12345');
        $doesNotExposeKeyInUrl = ! str_contains($request->url(), 'key=');
        $promptText = data_get($request->data(), 'contents.0.parts.0.text', '');
        $hasEscapedQuotes = str_contains($promptText, 'Input Title: "Fix \"critical\" SQL vulnerability"');

        return $hasSecureHeader && $doesNotExposeKeyInUrl && $hasEscapedQuotes;
    });
});

test('gemini api falls back to secondary model when primary model returns an error', function () {
    config([
        'services.gemini.key' => 'test-fallback-key',
        'services.gemini.model' => 'gemini-nonexistent',
    ]);

    Http::fake([
        '*/models/gemini-nonexistent:generateContent' => Http::response([
            'error' => ['message' => 'models/gemini-nonexistent is not found'],
        ], 404),
        '*/models/gemini-3.8-flash:generateContent' => Http::response([
            'candidates' => [[
                'content' => ['parts' => [[
                    'text' => "Here is your plan:\n```json\n".json_encode([
                        'plan_type' => 'task',
                        'outcome' => 'Completed fallback test outcome.',
                        'subtasks' => [
                            ['title' => 'Step 1 from fallback model', 'estimated_minutes' => 15],
                            ['title' => 'Step 2 from fallback model', 'estimated_minutes' => 20],
                            ['title' => 'Step 3 from fallback model', 'estimated_minutes' => 25],
                            ['title' => 'Step 4 from fallback model', 'estimated_minutes' => 30],
                        ],
                        'suggested_priority' => 'medium',
                        'suggested_category' => 'Dev',
                        'suggested_tags' => ['fallback'],
                        'summary' => 'Executed smoothly.',
                    ])."\n```\nEnjoy!",
                ]]],
            ]],
        ]),
    ]);

    $response = $this->postJson(route('tasks.ai.breakdown'), [
        'title' => 'Test resilient model fallback',
        'lang' => 'en',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('source', 'gemini')
        ->assertJsonPath('model', 'gemini-3.8-flash');

    expect($response->json('subtasks'))->toHaveCount(4)
        ->and($response->json('subtasks.0.title'))->toBe('Step 1 from fallback model');
});

test('standup brief accurately calculates completion rate and total count', function () {
    Task::create([
        'user_id' => $this->user->id,
        'title' => 'Completed Task A',
        'status' => 'completed',
    ]);
    Task::create([
        'user_id' => $this->user->id,
        'title' => 'Completed Task B',
        'status' => 'completed',
    ]);
    Task::create([
        'user_id' => $this->user->id,
        'title' => 'Pending Task C',
        'status' => 'pending',
    ]);
    Task::create([
        'user_id' => $this->user->id,
        'title' => 'In Progress Task D',
        'status' => 'in_progress',
    ]);

    $response = $this->getJson(route('tasks.ai.standup-brief', ['lang' => 'en']));

    $response->assertOk()
        ->assertJsonPath('brief.total_count', 4)
        ->assertJsonPath('brief.completed_count', 2)
        ->assertJsonPath('brief.completion_rate', 50);
});

test('chat messages are automatically pruned when exceeding max history threshold', function () {
    for ($i = 1; $i <= 105; $i++) {
        AiChatMessage::create([
            'user_id' => $this->user->id,
            'role' => 'user',
            'message' => "Message number {$i}",
        ]);
    }

    expect(AiChatMessage::where('user_id', $this->user->id)->count())->toBe(105);

    $service = app(AiTaskCopilotService::class);
    $pruned = $service->pruneOldChatMessages($this->user, 100);

    expect($pruned)->toBe(5)
        ->and(AiChatMessage::where('user_id', $this->user->id)->count())->toBe(100);
});

test('subtask breakdown generates unique IDs across multiple calls on the same task', function () {
    $service = app(AiTaskCopilotService::class);

    $result1 = $service->breakdown('Build mobile navigation menu');
    $result2 = $service->breakdown('Build mobile navigation menu');

    $ids1 = array_column($result1['subtasks'], 'id');
    $ids2 = array_column($result2['subtasks'], 'id');

    expect(count($ids1))->toBeGreaterThan(0)
        ->and(count($ids2))->toBeGreaterThan(0);

    // Ensure IDs in call 1 and call 2 do not collide
    $overlap = array_intersect($ids1, $ids2);
    expect($overlap)->toBeEmpty();
});

test('chat direct task creation truncates overly long titles without database errors', function () {
    $veryLongTitle = str_repeat('Long Task Title Word ', 30); // ~630 characters

    $response = $this->postJson(route('tasks.ai.chat'), [
        'message' => 'create task: '.$veryLongTitle,
        'lang' => 'en',
    ]);

    $response->assertOk()
        ->assertJsonPath('action_type', 'task_draft');

    $this->postJson(route('tasks.ai.drafts.confirm', $response->json('action_data.draft_id')))
        ->assertOk();

    $createdTask = Task::where('user_id', $this->user->id)->latest('id')->first();
    expect($createdTask)->not->toBeNull()
        ->and(mb_strlen($createdTask->title))->toBeLessThanOrEqual(255);
});

test('chat can directly mark existing task as completed', function () {
    $task = Task::create([
        'user_id' => $this->user->id,
        'title' => 'Complete Quarterly Audit',
        'status' => 'pending',
    ]);

    $response = $this->postJson(route('tasks.ai.chat'), [
        'message' => 'complete task: Complete Quarterly Audit',
        'lang' => 'en',
    ]);

    $response->assertOk()
        ->assertJsonPath('action_type', 'task_completed');

    expect($task->fresh()->status)->toBe('completed');
});

test('chat refuses to complete an ambiguous task title', function () {
    $first = Task::create([
        'user_id' => $this->user->id,
        'title' => 'Review quarterly report',
        'status' => 'pending',
    ]);
    $second = Task::create([
        'user_id' => $this->user->id,
        'title' => 'Review quarterly budget',
        'status' => 'pending',
    ]);

    $this->postJson(route('tasks.ai.chat'), [
        'message' => 'complete task: quarterly',
        'lang' => 'en',
    ])->assertOk()
        ->assertJsonPath('action_type', 'task_disambiguation')
        ->assertJsonCount(2, 'action_data.candidates');

    expect($first->fresh()->status)->toBe('pending')
        ->and($second->fresh()->status)->toBe('pending');
});

test('nlp parser treats negative urgency as low priority', function (string $text) {
    $this->postJson(route('tasks.ai.parse-nlp'), ['text' => $text])
        ->assertOk()
        ->assertJsonPath('parsed.priority', 'low');
})->with([
    'english' => 'Prepare meeting notes, not urgent',
    'khmer' => 'រៀបចំកំណត់ត្រាប្រជុំ មិនបន្ទាន់',
]);

test('ai nlp parser accurately handles Khmer keywords for dates, priority, and categories', function () {
    $response = $this->postJson(route('tasks.ai.parse-nlp'), [
        'text' => 'កែកូដប្រព័ន្ធ login ថ្ងៃស្អែក បន្ទាន់',
    ]);

    $response->assertOk();
    $parsed = $response->json('parsed');

    expect($parsed['priority'])->toBe('high')
        ->and($parsed['category'])->toBe('Dev')
        ->and($parsed['due_date'])->toBe(now()->addDay()->toDateString())
        ->and($parsed['title'])->toContain('កែកូដប្រព័ន្ធ login');
});

test('gemini tool calling preserves tags and creates task successfully', function () {
    config()->set('services.gemini.key', 'fake-gemini-key');

    Http::fake([
        'https://generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [[
                'content' => [
                    'parts' => [
                        [
                            'functionCall' => [
                                'name' => 'create_task',
                                'args' => [
                                    'title' => 'Design user dashboard analytics widgets',
                                    'category' => 'Design',
                                    'priority' => 'high',
                                    'due_date' => now()->addDays(2)->toDateString(),
                                    'tags' => ['ui', 'analytics', 'dashboard'],
                                    'description' => 'Detailed Figma mockups and tokens.',
                                    'plan_type' => 'project',
                                    'subtasks' => [
                                        ['title' => 'Define dashboard analytics requirements', 'estimated_minutes' => 20],
                                        ['title' => 'Create the dashboard wireframe', 'estimated_minutes' => 30],
                                        ['title' => 'Design analytics widget states', 'estimated_minutes' => 45],
                                        ['title' => 'Review accessibility and handoff details', 'estimated_minutes' => 20],
                                    ],
                                    'summary' => 'Review each widget with engineering before handoff.',
                                ],
                            ],
                        ],
                    ],
                ],
            ]],
        ]),
    ]);

    $response = $this->postJson(route('tasks.ai.chat'), [
        'message' => 'Please create a design task for dashboard analytics',
        'lang' => 'en',
    ]);

    $response->assertOk()
        ->assertJsonPath('action_type', 'task_draft');

    $this->postJson(route('tasks.ai.drafts.confirm', $response->json('action_data.draft_id')))
        ->assertOk();

    $task = Task::where('user_id', $this->user->id)->latest('id')->first();
    expect($task)->not->toBeNull()
        ->and($task->title)->toBe('Design user dashboard analytics widgets')
        ->and($task->category)->toBe('Design')
        ->and($task->priority)->toBe('high')
        ->and($task->tags)->toEqual(['ui', 'analytics', 'dashboard'])
        ->and($task->subtasks)->toHaveCount(4)
        ->and($task->subtasks[0]['title'])->toBe('Define dashboard analytics requirements');

    Http::assertSentCount(1);
});

test('gemini tool arguments are normalized before task creation', function () {
    config()->set('services.gemini.key', 'fake-gemini-key');

    Http::fake([
        'https://generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [[
                'content' => [
                    'parts' => [[
                        'functionCall' => [
                            'name' => 'create_task',
                            'args' => [
                                'title' => '<b>Ship secure release</b>',
                                'category' => 'dev',
                                'priority' => 'INVALID',
                                'due_date' => '2026-99-99',
                                'tags' => array_merge(['Release', 'release'], array_map(fn ($i) => "tag-{$i}", range(1, 15))),
                                'description' => '<script>alert(1)</script>Prepare the release notes.',
                            ],
                        ],
                    ]],
                ],
            ]],
        ]),
    ]);

    $response = $this->postJson(route('tasks.ai.chat'), [
        'message' => 'Please save a release task for me',
        'lang' => 'en',
    ])->assertOk()
        ->assertJsonPath('action_type', 'task_draft')
        ->assertJsonPath('meta.source', 'gemini');

    $this->postJson(route('tasks.ai.drafts.confirm', $response->json('action_data.draft_id')))
        ->assertOk();

    $task = Task::where('user_id', $this->user->id)->latest('id')->firstOrFail();
    expect($task->title)->toBe('Ship secure release')
        ->and($task->category)->toBe('Dev')
        ->and($task->priority)->toBe('medium')
        ->and($task->due_date)->toBeNull()
        ->and($task->tags)->toHaveCount(10)
        ->and($task->tags)->toContain('Release')
        ->and($task->tags)->not->toContain('release')
        ->and($task->description)->not->toContain('<script>')
        ->and($task->subtasks)->toHaveCount(5);

    Http::assertSentCount(1);
});
