<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Services\AiTaskCopilotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            'lang' => 'nullable|string|in:en,km',
        ]);

        $lang = $validated['lang'] ?? 'en';
        $result = $this->aiService->breakdown(
            $validated['title'],
            $validated['description'] ?? null,
            $validated['category'] ?? null,
            $lang
        );

        return response()->json($result);
    }

    /**
     * 1-Click AI Breakdown for an existing saved task
     */
    public function breakdownExistingTask(Request $request, Task $task): JsonResponse
    {
        abort_unless($task->user_id === $request->user()->id, 404);

        $lang = $request->input('lang', 'en');
        $result = $this->aiService->breakdown(
            $task->title,
            $task->description,
            $task->category,
            $lang
        );

        $existingSubtasks = $task->subtasks ?? [];
        $newSubtasks = $result['subtasks'] ?? [];

        // Append new subtasks, keeping existing ones
        $merged = array_merge($existingSubtasks, $newSubtasks);
        $task->subtasks = $merged;

        if (empty($task->category) && ! empty($result['suggested_category'])) {
            $task->category = $result['suggested_category'];
        }

        $task->save();

        return response()->json([
            'success' => true,
            'message' => $lang === 'km' ? 'បានបង្កើតកិច្ចការរងដោយជោគជ័យ!' : 'AI Subtasks generated successfully!',
            'task' => $task->fresh(),
            'subtasks' => $task->subtasks,
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
        $lang = $request->input('lang', 'en');
        $tasks = $request->user()->tasks()->get();

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

        return response()->json($result);
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
            'status' => $hasKey ? 'connected' : 'ready_heuristic',
            'context_window' => '1,048,576 tokens',
            'backend' => 'Laravel 12.x (PHP '.PHP_VERSION.')',
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
