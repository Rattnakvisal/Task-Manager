<?php

namespace App\Http\Controllers;

use App\Models\AiChatMessage;
use App\Models\Task;
use App\Services\AiTaskCopilotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AiCopilotController extends Controller
{
    public function __construct(
        protected AiTaskCopilotService $aiService
    ) {}

    /**
     * AI Magic Breakdown: Generate actionable subtasks from title & description
     */
    public function breakdown(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'category' => 'nullable|string|max:50',
            'plan_type' => 'nullable|string|in:auto,task,learning,project,personal',
            'lang' => 'nullable|string|in:en,km',
        ]);

        $lang = $validated['lang'] ?? 'en';
        $result = $this->aiService->breakdown(
            $validated['title'],
            $validated['description'] ?? null,
            $validated['category'] ?? null,
            $lang,
            $validated['plan_type'] ?? 'auto',
            $request->user(),
        );

        return response()->json($result);
    }

    /**
     * 1-Click AI Breakdown for an existing saved task
     */
    public function breakdownExistingTask(Request $request, Task $task): JsonResponse
    {
        abort_unless($task->user_id === $request->user()->id, 404);

        $validated = $request->validate([
            'lang' => 'nullable|string|in:en,km',
            'plan_type' => 'nullable|string|in:auto,task,learning,project,personal',
        ]);

        $lang = $validated['lang'] ?? 'en';
        $planType = $validated['plan_type'] ?? 'auto';
        $result = $this->aiService->breakdown(
            $task->title,
            $task->description,
            $task->category,
            $lang,
            $planType,
            $request->user(),
        );

        $existingSubtasks = is_array($task->subtasks) ? $task->subtasks : [];
        $newSubtasks = $result['subtasks'] ?? [];

        // Keep this action idempotent: repeated AI clicks must not duplicate the same checklist.
        $knownTitles = collect($existingSubtasks)
            ->pluck('title')
            ->filter(fn ($title) => is_string($title) && trim($title) !== '')
            ->map(fn ($title) => Str::lower(preg_replace('/\s+/u', ' ', trim($title))))
            ->flip();
        $uniqueNewSubtasks = collect($newSubtasks)
            ->filter(function ($subtask) use ($knownTitles) {
                if (! is_array($subtask) || ! is_string($subtask['title'] ?? null)) {
                    return false;
                }

                $normalizedTitle = Str::lower(preg_replace('/\s+/u', ' ', trim($subtask['title'])));
                if ($normalizedTitle === '' || $knownTitles->has($normalizedTitle)) {
                    return false;
                }

                $knownTitles->put($normalizedTitle, true);

                return true;
            })
            ->values()
            ->all();

        $merged = array_merge($existingSubtasks, $uniqueNewSubtasks);
        $task->subtasks = $merged;

        if (empty($task->category) && ! empty($result['suggested_category'])) {
            $task->category = $result['suggested_category'];
        }

        $task->save();

        return response()->json([
            'success' => true,
            'message' => empty($uniqueNewSubtasks)
                ? ($lang === 'km' ? 'មិនមានជំហានថ្មីដែលត្រូវបន្ថែមទេ។' : 'No new checklist steps were needed.')
                : ($lang === 'km' ? 'បានបង្កើតកិច្ចការរងដោយជោគជ័យ!' : 'AI Subtasks generated successfully!'),
            'task' => $task->fresh(),
            'subtasks' => $task->subtasks,
            'added_subtasks_count' => count($uniqueNewSubtasks),
            'subtasks_count' => $task->subtasks_count,
            'completed_subtasks_count' => $task->completed_subtasks_count,
            'subtasks_progress' => $task->subtasks_progress,
            'summary' => $result['summary'] ?? null,
        ]);
    }

    /**
     * AI Enhance: Polish title and draft structured description
     */
    public function enhance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'lang' => 'nullable|string|in:en,km',
        ]);

        $lang = $validated['lang'] ?? 'en';
        $result = $this->aiService->enhance(
            $validated['title'],
            $validated['description'] ?? null,
            $lang
        );

        return response()->json($result);
    }

    /**
     * Analyze a form once and return a complete, reviewable suggestion.
     */
    public function analyze(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'category' => 'nullable|string|max:50',
            'plan_type' => 'nullable|string|in:auto,task,learning,project,personal',
            'lang' => 'nullable|string|in:en,km',
        ]);

        $lang = $validated['lang'] ?? 'en';
        $enhanced = $this->aiService->enhance(
            $validated['title'],
            $validated['description'] ?? null,
            $lang,
        );
        $breakdown = $this->aiService->breakdown(
            $enhanced['title'] ?? $validated['title'],
            $enhanced['description'] ?? ($validated['description'] ?? null),
            $validated['category'] ?? ($enhanced['suggested_category'] ?? null),
            $lang,
            $validated['plan_type'] ?? 'auto',
            $request->user(),
        );

        return response()->json(array_merge($breakdown, [
            'success' => true,
            'title' => $enhanced['title'] ?? $validated['title'],
            'description' => $enhanced['description'] ?? ($validated['description'] ?? ''),
            'suggested_category' => $breakdown['suggested_category'] ?? ($enhanced['suggested_category'] ?? null),
            'suggested_priority' => $breakdown['suggested_priority'] ?? ($enhanced['suggested_priority'] ?? null),
            'source' => ($breakdown['source'] ?? null) === 'gemini' || ($enhanced['source'] ?? null) === 'gemini'
                ? 'gemini'
                : 'smart_heuristic',
        ]));
    }

    /**
     * Parse natural language task input (e.g. "Review audit report Friday 3pm #Finance")
     */
    public function parseNlp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'text' => 'required|string|max:500',
        ]);

        $parsed = $this->aiService->parseNlp($validated['text']);

        return response()->json([
            'success' => true,
            'parsed' => $parsed,
        ]);
    }

    /**
     * Generate daily AI Standup Briefing
     */
    public function standupBrief(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lang' => 'nullable|string|in:en,km',
        ]);
        $lang = $validated['lang'] ?? 'en';
        $tasks = $request->user()->tasks()
            ->select(['id', 'user_id', 'title', 'status', 'priority', 'category', 'due_date'])
            ->get();

        $brief = $this->aiService->generateStandupBrief($tasks, $lang);

        return response()->json([
            'success' => true,
            'brief' => $brief,
        ]);
    }

    /**
     * AI Chatbot message endpoint
     */
    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:2000',
            'lang' => 'nullable|string|in:en,km',
        ]);

        $lang = $validated['lang'] ?? 'en';
        $result = $this->aiService->chat($request->user(), $validated['message'], $lang);

        if (($result['action_type'] ?? null) === 'task_draft' && is_array($result['action_data'] ?? null)) {
            $draftId = (string) Str::uuid();
            $expiresAt = now()->addMinutes(15);
            $drafts = collect($request->session()->get('ai_task_drafts', []))
                ->filter(fn ($draft) => is_array($draft) && ($draft['expires_at'] ?? 0) > now()->timestamp)
                ->take(-9)
                ->all();

            $result['action_data']['draft_id'] = $draftId;
            $result['action_data']['expires_at'] = $expiresAt->toIso8601String();
            $drafts[$draftId] = [
                'payload' => $result['action_data'],
                'message_id' => $result['message_id'] ?? null,
                'expires_at' => $expiresAt->timestamp,
            ];
            $request->session()->put('ai_task_drafts', $drafts);

            if (! empty($result['message_id'])) {
                AiChatMessage::where('id', $result['message_id'])
                    ->where('user_id', $request->user()->id)
                    ->update(['action_data' => $result['action_data']]);
            }
        }

        return response()->json($result);
    }

    /**
     * Confirm a previously generated draft. The session payload is authoritative;
     * no task fields are accepted from the browser.
     */
    public function confirmDraft(Request $request, string $draftId): JsonResponse
    {
        abort_unless(Str::isUuid($draftId), 404);

        $drafts = $request->session()->get('ai_task_drafts', []);
        $stored = $drafts[$draftId] ?? null;
        if (! is_array($stored) || ! is_array($stored['payload'] ?? null)) {
            return response()->json([
                'success' => false,
                'message' => 'This AI draft is no longer available. Please generate it again.',
            ], 410);
        }

        if (($stored['expires_at'] ?? 0) < now()->timestamp) {
            unset($drafts[$draftId]);
            $request->session()->put('ai_task_drafts', $drafts);

            return response()->json([
                'success' => false,
                'message' => 'This AI draft expired. Please generate a fresh draft.',
            ], 410);
        }

        $task = $this->aiService->confirmTaskDraft($request->user(), $draftId, $stored['payload']);
        $actionData = $this->aiService->confirmedTaskActionData($task, $stored['payload']);

        if (! empty($stored['message_id'])) {
            AiChatMessage::where('id', $stored['message_id'])
                ->where('user_id', $request->user()->id)
                ->update([
                    'action_type' => 'task_created',
                    'action_data' => $actionData,
                ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Task created successfully.',
            'action_type' => 'task_created',
            'action_data' => $actionData,
        ]);
    }

    /**
     * Undo a recent Nova-created task without exposing general delete behavior.
     */
    public function undoCreatedTask(Request $request, Task $task): JsonResponse
    {
        abort_unless($task->user_id === $request->user()->id, 404);
        abort_unless($task->ai_request_id && $task->created_at?->greaterThanOrEqualTo(now()->subMinutes(10)), 409);

        $taskId = $task->id;
        $task->delete();
        $request->user()->notifications()
            ->get()
            ->filter(fn ($notification) => (int) ($notification->data['task_id'] ?? 0) === $taskId)
            ->each->delete();

        return response()->json([
            'success' => true,
            'message' => 'AI-created task removed.',
        ]);
    }

    /**
     * Get Chatbot conversation history
     */
    public function chatHistory(Request $request): JsonResponse
    {
        $history = $this->aiService->getChatHistory($request->user());

        return response()->json([
            'success' => true,
            'history' => $history->map(fn ($msg) => [
                'id' => $msg->id,
                'role' => $msg->role,
                'message' => $msg->message,
                'action_type' => $msg->action_type,
                'action_data' => $msg->action_data,
                'time' => $msg->created_at->format('h:i A'),
            ]),
        ]);
    }

    /**
     * Clear Chatbot conversation history
     */
    public function clearChat(Request $request): JsonResponse
    {
        $this->aiService->clearChatHistory($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Chat history cleared successfully',
        ]);
    }

    /**
     * Get the signed-in user's AI personalization profile.
     */
    public function preferences(Request $request): JsonResponse
    {
        $preferences = $request->user()->aiPreference()->first();

        return response()->json([
            'success' => true,
            'configured' => $preferences !== null,
            'preferences' => $preferences ? [
                'occupation' => $preferences->occupation,
                'experience_level' => $preferences->experience_level,
                'learning_interests' => $preferences->learning_interests ?? [],
                'work_skills' => $preferences->work_skills ?? [],
                'assistance_areas' => $preferences->assistance_areas ?? [],
                'other_needs' => $preferences->other_needs,
            ] : null,
        ]);
    }

    /**
     * Create or update the user's AI personalization profile.
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'occupation' => 'required|string|in:student,software_it,education,business,design_creative,marketing_sales,finance,healthcare,engineering,government_ngo,freelancer,other',
            'experience_level' => 'required|string|in:beginner,intermediate,advanced',
            'learning_interests' => 'required|array|min:1|max:11',
            'learning_interests.*' => 'string|distinct|in:technology,languages,business,design_creative,finance,marketing_sales,leadership,health_wellness,academic,life_skills,other',
            'work_skills' => 'nullable|array|max:9',
            'work_skills.*' => 'string|distinct|in:communication,management,problem_solving,writing,data_analysis,digital_tools,customer_service,project_management,other',
            'assistance_areas' => 'required|array|min:1|max:8',
            'assistance_areas.*' => 'string|distinct|in:learning,work,personal,career,technical,writing_translation,planning,creativity',
            'other_needs' => 'nullable|string|max:1000',
        ]);

        $preferences = $request->user()->aiPreference()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated,
        );

        return response()->json([
            'success' => true,
            'message' => 'AI preferences saved successfully.',
            'preferences' => [
                'occupation' => $preferences->occupation,
                'experience_level' => $preferences->experience_level,
                'learning_interests' => $preferences->learning_interests ?? [],
                'work_skills' => $preferences->work_skills ?? [],
                'assistance_areas' => $preferences->assistance_areas ?? [],
                'other_needs' => $preferences->other_needs,
            ],
        ]);
    }

    /**
     * Get AI status info
     */
    public function itStatus(Request $request): JsonResponse
    {
        $hasKey = ! empty(config('services.gemini.key'));

        return response()->json([
            'success' => true,
            'provider' => 'Google Gemini',
            'model' => config('services.gemini.model', 'gemini-3.8-flash'),
            // A configured key is not proof of network connectivity or provider health.
            'status' => $hasKey ? 'configured' : 'fallback_only',
            'context_window' => config('services.gemini.context_window', '1,048,576 tokens'),
            'backend' => 'Laravel '.app()->version().' (PHP '.PHP_VERSION.')',
            'database' => strtoupper(config('database.default')),
            'capabilities' => [
                'full_stack_breakdown',
                'bug_triage_rca',
                'devops_docker_cicd',
                'database_optimization',
                'it_learning_and_tutoring',
                'work_writing_and_planning',
                'personal_productivity',
                'general_question_answering',
                'agile_standup_telemetry',
            ],
        ]);
    }
}
