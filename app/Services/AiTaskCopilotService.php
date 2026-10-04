<?php

namespace App\Services;

use App\Models\AiChatMessage;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiTaskCopilotService
{
    /**
     * Break down a task into actionable, sequential subtasks with priority and category recommendations.
     */
    public function breakdown(string $title, ?string $description = null, ?string $category = null, string $lang = 'en'): array
    {
        $apiKey = config('services.gemini.key');

        if (!empty($apiKey)) {
            $geminiResult = $this->callGeminiForBreakdown($apiKey, $title, $description, $category, $lang);
            if ($geminiResult !== null) {
                return $geminiResult;
            }
        }

        return $this->smartHeuristicBreakdown($title, $description, $category, $lang);
    }

    /**
     * Enhance and polish a task title and description into a clear, professional plan.
     */
    public function enhance(string $title, ?string $description = null, string $lang = 'en'): array
    {
        $apiKey = config('services.gemini.key');

        if (!empty($apiKey)) {
            $geminiResult = $this->callGeminiForEnhance($apiKey, $title, $description, $lang);
            if ($geminiResult !== null) {
                return $geminiResult;
            }
        }

        return $this->smartHeuristicEnhance($title, $description, $lang);
    }

    /**
     * Parse natural language text into task title, due_date, priority, category, and tags.
     * Example: "Fix login auth bug by Friday 5pm priority high #Dev"
     */
    public function parseNlp(string $input): array
    {
        $text = trim($input);
        $priority = 'medium';
        $category = null;
        $dueDate = null;
        $tags = [];

        // 1. Extract hashtags for Category & Tags
        if (preg_match_all('/#([a-zA-Z0-9_\-]+)/', $text, $matches)) {
            $validCategories = ['Work', 'Personal', 'Urgent', 'Design', 'Dev', 'Study', 'Finance'];
            $itTags = ['devops', 'backend', 'frontend', 'database', 'db', 'api', 'security', 'bug', 'infra', 'code', 'qa', 'sysadmin', 'cloud'];
            foreach ($matches[1] as $tag) {
                $matchedCat = null;
                $lowerTag = strtolower($tag);

                foreach ($validCategories as $validCat) {
                    if (strcasecmp($tag, $validCat) === 0) {
                        $matchedCat = $validCat;
                        break;
                    }
                }

                if (!$matchedCat && in_array($lowerTag, $itTags, true)) {
                    $matchedCat = 'Dev';
                }

                if ($matchedCat && !$category) {
                    $category = $matchedCat;
                }
                $tags[] = $lowerTag;
            }
            $text = preg_replace('/#[a-zA-Z0-9_\-]+/', '', $text);
        }

        // 2. Extract Priority expressions
        if (preg_match('/(?:!high\b|\b(?:p1|priority\s*:\s*high|priority\s+high|urgent|critical|asap)\b)/i', $text, $pMatch)) {
            $priority = 'high';
            $text = preg_replace('/(?:!high\b|\b(?:p1|priority\s*:\s*high|priority\s+high|urgent|critical|asap)\b)/i', '', $text);
        } elseif (preg_match('/(?:!medium\b|\b(?:p2|priority\s*:\s*medium|priority\s+medium|normal)\b)/i', $text, $pMatch)) {
            $priority = 'medium';
            $text = preg_replace('/(?:!medium\b|\b(?:p2|priority\s*:\s*medium|priority\s+medium|normal)\b)/i', '', $text);
        } elseif (preg_match('/(?:!low\b|\b(?:p3|priority\s*:\s*low|priority\s+low)\b)/i', $text, $pMatch)) {
            $priority = 'low';
            $text = preg_replace('/(?:!low\b|\b(?:p3|priority\s*:\s*low|priority\s+low)\b)/i', '', $text);
        }

        // 3. Extract Due Date expressions
        $now = Carbon::now();
        $datePatterns = [
            '/\b(today|tonight)\b/i' => fn () => $now->toDateString(),
            '/\b(tomorrow)\b/i' => fn () => $now->copy()->addDay()->toDateString(),
            '/\bin\s+(\d+)\s+days?\b/i' => fn ($m) => $now->copy()->addDays((int) $m[1])->toDateString(),
            '/\bnext\s+(monday|tuesday|wednesday|thursday|friday|saturday|sunday)\b/i' => function ($m) use ($now) {
                $day = strtolower($m[1]);
                return $now->copy()->next($day)->toDateString();
            },
            '/\b(?:this\s+|by\s+)?(monday|tuesday|wednesday|thursday|friday|saturday|sunday)\b/i' => function ($m) use ($now) {
                $day = strtolower($m[1]);
                $target = $now->copy()->next($day);
                if ($now->format('l') === ucfirst($day)) {
                    $target = $now->copy();
                }
                return $target->toDateString();
            },
            '/\bnext\s+week\b/i' => fn () => $now->copy()->addWeek()->startOfWeek()->toDateString(),
            '/\bend\s+of\s+week\b/i' => fn () => $now->copy()->endOfWeek()->toDateString(),
            '/\bby\s+(\d{4}-\d{2}-\d{2})\b/i' => fn ($m) => $m[1],
        ];

        foreach ($datePatterns as $pattern => $resolver) {
            if (preg_match($pattern, $text, $m)) {
                $dueDate = $resolver($m);
                $text = preg_replace($pattern, '', $text);
                break;
            }
        }

        // Clean extra prepositions like "by", "on", "at", extra spaces
        $cleanTitle = trim(preg_replace('/\s+/', ' ', preg_replace('/\b(by|due|at|on|for)\s*$/i', '', $text)));
        $cleanTitle = trim($cleanTitle, " \t\n\r\0\x0B-:,");

        if (empty($cleanTitle)) {
            $cleanTitle = trim($input);
        }

        // Fallback category detection if none specified
        if (!$category) {
            $category = $this->detectCategory($cleanTitle);
        }

        return [
            'original' => $input,
            'title' => $cleanTitle,
            'priority' => $priority,
            'category' => $category,
            'due_date' => $dueDate,
            'tags' => array_values(array_unique($tags)),
        ];
    }

    /**
     * Generate a personalized daily AI Standup Briefing based on user tasks.
     */
    public function generateStandupBrief(Collection $tasks, string $lang = 'en'): array
    {
        $total = $tasks->count();
        $completed = $tasks->where('status', 'completed')->count();
        $inProgress = $tasks->where('status', 'in_progress')->count();
        $pending = $tasks->where('status', 'pending')->count();
        $overdue = $tasks->filter->is_overdue->values();
        $highPriority = $tasks->where('priority', 'high')->where('status', '!=', 'completed')->values();
        $dueToday = $tasks->filter(fn ($t) => $t->due_date && $t->due_date->isToday() && $t->status !== 'completed')->values();

        $focusTask = $overdue->first() ?? $highPriority->first() ?? $dueToday->first() ?? $tasks->where('status', '!=', 'completed')->first();

        $isKhmer = ($lang === 'km');

        if ($total === 0) {
            return [
                'headline' => $isKhmer ? 'កន្លែងធ្វើការរបស់អ្នកទទេស្អាត!' : 'Your Workspace is Fresh & Ready!',
                'summary' => $isKhmer
                    ? 'អ្នកមិនទាន់មានកិច្ចការនៅឡើយទេ។ ចុចបង្កើតកិច្ចការថ្មី ឬប្រើ AI Magic Breakdown ដើម្បីចាប់ផ្តើម!'
                    : 'No tasks scheduled yet. Create your first task or use AI Magic Breakdown to plan your day!',
                'focus_task' => null,
                'overdue_count' => 0,
                'urgent_count' => 0,
                'due_today_count' => 0,
                'completed_count' => 0,
                'action_recommendation' => $isKhmer ? 'ចាប់ផ្តើមដោយការរៀបចំផែនការការងារសប្តាហ៍នេះ។' : 'Start by outlining 3 key outcomes you want to achieve today.',
            ];
        }

        if ($overdue->count() > 0) {
            $headline = $isKhmer
                ? "យកចិត្តទុកដាក់៖ មាន {$overdue->count()} កិច្ចការហួសកាលកំណត់!"
                : "Heads up: You have {$overdue->count()} overdue item" . ($overdue->count() > 1 ? 's' : '') . "!";
            $recommendation = $isKhmer
                ? "សូមដោះស្រាយកិច្ចការ \"{$overdue->first()->title}\" ជាមុនសិន ឬពន្យារពេលកំណត់ដើម្បីកុំឱ្យរំខានកាលវិភាគ។"
                : "Knock out \"{$overdue->first()->title}\" first or reschedule its deadline to stay on track.";
        } elseif ($highPriority->count() > 0) {
            $headline = $isKhmer
                ? "ថ្ងៃនេះមាន {$highPriority->count()} កិច្ចការអាទិភាពខ្ពស់សំខាន់!"
                : "High-impact focus: {$highPriority->count()} critical task" . ($highPriority->count() > 1 ? 's' : '') . " pending!";
            $recommendation = $isKhmer
                ? "ផ្ដោតកម្លាំងលើ \"{$highPriority->first()->title}\" មុនគេក្នុងម៉ោងផលិតភាពខ្ពស់បំផុតរបស់អ្នក។"
                : "Tackle \"{$highPriority->first()->title}\" during your peak focus window today.";
        } else {
            $headline = $isKhmer
                ? 'លំហូរការងាររបស់អ្នកមានស្ថិរភាពល្អប្រសើរ!'
                : 'Great momentum! Your tasks are well organized.';
            $recommendation = $isKhmer
                ? 'បន្តជំរុញកិច្ចការដែលកំពុងដំណើរការដើម្បីបញ្ចប់គោលដៅប្រចាំថ្ងៃ។'
                : 'Keep driving in-progress items forward to wrap up milestones ahead of time.';
        }

        $summary = $isKhmer
            ? "អ្នកបានបញ្ចប់ {$completed} ក្នុងចំណោម {$total} កិច្ចការ ({$inProgress} កំពុងធ្វើ, {$pending} មិនទាន់ធ្វើ)។"
            : "You have completed {$completed} of {$total} tasks ({$inProgress} in progress, {$pending} pending).";

        return [
            'headline' => $headline,
            'summary' => $summary,
            'focus_task' => $focusTask ? [
                'id' => $focusTask->id,
                'title' => $focusTask->title,
                'priority' => $focusTask->priority,
                'category' => $focusTask->category,
                'due_date' => $focusTask->due_date?->format('M d, Y'),
            ] : null,
            'overdue_count' => $overdue->count(),
            'urgent_count' => $highPriority->count(),
            'due_today_count' => $dueToday->count(),
            'completed_count' => $completed,
            'action_recommendation' => $recommendation,
        ];
    }

    /**
     * Gemini API integration for generating subtasks
     */
    protected function callGeminiForBreakdown(string $apiKey, string $title, ?string $description, ?string $category, string $lang): ?array
    {
        $model = config('services.gemini.model', 'gemini-2.5-flash');
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $langName = ($lang === 'km') ? 'Khmer' : 'English';

        $prompt = <<<PROMPT
You are an expert AI task management assistant and productivity copilot.
Break down the following task into 3 to 5 clear, sequential, and highly actionable subtasks/checklist items.
Respond in {$langName} language.

Task Title: "{$title}"
Task Description: "{$description}"
Category: "{$category}"

You MUST respond strictly with a valid JSON object in this exact schema:
{
  "subtasks": [
    {"id": "ai_1", "title": "First actionable step", "completed": false, "estimated_minutes": 25},
    {"id": "ai_2", "title": "Second actionable step", "completed": false, "estimated_minutes": 30}
  ],
  "suggested_priority": "high" | "medium" | "low",
  "suggested_category": "Work" | "Personal" | "Urgent" | "Design" | "Dev" | "Study" | "Finance",
  "estimated_minutes": 90,
  "suggested_tags": ["tag1", "tag2"],
  "summary": "Short motivating tip on executing this task effectively in {$langName}."
}
Do not include markdown code block backticks (like ```json), just raw JSON.
PROMPT;

        try {
            $response = Http::timeout(8)->post($endpoint, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'temperature' => 0.4,
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                if ($content) {
                    $decoded = json_decode(trim($content), true);
                    if (is_array($decoded) && !empty($decoded['subtasks'])) {
                        // Ensure IDs and format
                        foreach ($decoded['subtasks'] as $i => &$st) {
                            $st['id'] = $st['id'] ?? ('ai_' . ($i + 1) . '_' . time());
                            $st['completed'] = false;
                        }
                        $decoded['success'] = true;
                        $decoded['source'] = 'gemini';
                        return $decoded;
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Gemini task breakdown API call failed: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Gemini API call for task enhancement
     */
    protected function callGeminiForEnhance(string $apiKey, string $title, ?string $description, string $lang): ?array
    {
        $model = config('services.gemini.model', 'gemini-2.5-flash');
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
        $langName = ($lang === 'km') ? 'Khmer' : 'English';

        $prompt = <<<PROMPT
You are a professional task strategist. Polish and enhance the following task title and description to make it professional, clear, and actionable with clear deliverables.
Respond in {$langName}.

Input Title: "{$title}"
Input Description: "{$description}"

Respond ONLY with this JSON schema:
{
  "title": "Enhanced action-oriented title",
  "description": "Structured description with objectives and definition of done",
  "suggested_category": "Work" | "Personal" | "Urgent" | "Design" | "Dev" | "Study" | "Finance",
  "suggested_priority": "high" | "medium" | "low",
  "suggested_tags": ["tag1", "tag2"]
}
PROMPT;

        try {
            $response = Http::timeout(8)->post($endpoint, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'temperature' => 0.4,
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                if ($content) {
                    $decoded = json_decode(trim($content), true);
                    if (is_array($decoded) && !empty($decoded['title'])) {
                        $decoded['success'] = true;
                        $decoded['source'] = 'gemini';
                        return $decoded;
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Gemini task enhance API call failed: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Highly intelligent domain-aware rule engine for instant subtask generation without API keys
     */
    protected function smartHeuristicBreakdown(string $title, ?string $description, ?string $category, string $lang): array
    {
        $t = mb_strtolower($title);
        $d = mb_strtolower($description ?? '');
        $combined = $t . ' ' . $d;

        $isKhmer = ($lang === 'km');

        $subtasks = [];
        $priority = 'medium';
        $detectedCategory = $category ?: $this->detectCategory($title);
        $tags = [];
        $summary = '';

        // Priority heuristics
        if (preg_match('/(urgent|asap|critical|fix|bug|broken|crash|error|emergency|deadline|today|បន្ទាន់|បញ្ហា)/u', $combined)) {
            $priority = 'high';
        } elseif (preg_match('/(idea|explore|someday|read|later|optional|ស្វែងយល់|អាន)/u', $combined)) {
            $priority = 'low';
        }

        // Domain matching
        if (preg_match('/(bug|fix|error|crash|issue|patch|hotfix|404|500|exception|fail|បញ្ហា|កែកំហុស)/u', $combined)) {
            $detectedCategory = $detectedCategory ?: 'Dev';
            $tags = ['bugfix', 'quality'];
            $subtasks = $isKhmer ? [
                ['title' => 'កំណត់ និងបង្កើតឡើងវិញនូវកំហុស (Reproduce bug & inspect logs)', 'estimated_minutes' => 15],
                ['title' => 'វិភាគមូលហេតុដើម និងបង្កើត automated test បញ្ជាក់កំហុស', 'estimated_minutes' => 25],
                ['title' => 'អនុវត្តកូដកែសម្រួល (Implement bugfix)', 'estimated_minutes' => 30],
                ['title' => 'សាកល្បង regression test និងដាក់ឱ្យដំណើរការឡើងវិញ', 'estimated_minutes' => 15],
            ] : [
                ['title' => 'Reproduce bug locally & review stack trace logs', 'estimated_minutes' => 15],
                ['title' => 'Identify root cause & create failing reproduction test', 'estimated_minutes' => 25],
                ['title' => 'Implement fix and verify code quality', 'estimated_minutes' => 30],
                ['title' => 'Run regression tests & verify deployment in staging', 'estimated_minutes' => 15],
            ];
            $summary = $isKhmer ? 'យុទ្ធសាស្ត្រដោះស្រាយកំហុស ៤ ជំហានដើម្បីជៀសវាងបញ្ហាដដែលៗ។' : '4-step structured debugging workflow to resolve the issue reliably.';
        } elseif (preg_match('/(feature|api|backend|frontend|code|develop|build|database|migration|endpoint|crud|controller|auth|react|vue|laravel|flutter|សរសេរកូដ|បង្កើត)/u', $combined)) {
            $detectedCategory = $detectedCategory ?: 'Dev';
            $tags = ['feature', 'coding'];
            $subtasks = $isKhmer ? [
                ['title' => 'កំណត់តម្រូវការមុខងារ និងគ្រោង Schema/Architecture', 'estimated_minutes' => 20],
                ['title' => 'បង្កើត Database migration, Model និង API endpoints/Services', 'estimated_minutes' => 40],
                ['title' => 'រចនា UI components និងភ្ជាប់ទៅកាន់ Backend APIs', 'estimated_minutes' => 45],
                ['title' => 'សរសេរ Automated Feature tests និងផ្ទៀងផ្ទាត់ UX', 'estimated_minutes' => 25],
            ] : [
                ['title' => 'Define technical requirements & schema/architecture draft', 'estimated_minutes' => 20],
                ['title' => 'Build database migrations, models & API endpoints', 'estimated_minutes' => 40],
                ['title' => 'Implement frontend UI components & integrate APIs', 'estimated_minutes' => 45],
                ['title' => 'Write feature test assertions & verify edge cases', 'estimated_minutes' => 25],
            ];
            $summary = $isKhmer ? 'លំហូរអភិវឌ្ឍន៍មុខងារពេញលេញពី Backend ដល់ Frontend។' : 'Full-stack development steps from architecture to testing.';
        } elseif (preg_match('/(design|ui|ux|figma|logo|banner|landing|poster|prototype|wireframe|mockup|រចនា|គំនូរ)/u', $combined)) {
            $detectedCategory = $detectedCategory ?: 'Design';
            $tags = ['design', 'ui-ux'];
            $subtasks = $isKhmer ? [
                ['title' => 'ស្រាវជ្រាវគំរូរចនា និងប្រមូល Inspiration/Moodboard', 'estimated_minutes' => 25],
                ['title' => 'បង្កើត Wireframe និងកំណត់រចនាសម្ព័ន្ធព័ត៌មាន (Information Hierarchy)', 'estimated_minutes' => 35],
                ['title' => 'រចនា High-fidelity Mockups ជាមួយ Colors & Typography', 'estimated_minutes' => 60],
                ['title' => 'ត្រួតពិនិត្យភាពត្រូវគ្នាលើ Mobile & Desktop និង Export assets', 'estimated_minutes' => 20],
            ] : [
                ['title' => 'Gather visual references, moodboard & competitor inspiration', 'estimated_minutes' => 25],
                ['title' => 'Create low-fidelity wireframes & component layout', 'estimated_minutes' => 35],
                ['title' => 'Design high-fidelity mockups with typography & tokens', 'estimated_minutes' => 60],
                ['title' => 'Test mobile responsive states & export production assets', 'estimated_minutes' => 20],
            ];
            $summary = $isKhmer ? 'ដំណើរការរចនាបែបស្តង់ដារពីគំនិតដល់ការនាំចេញឯកសារ។' : 'End-to-end design pipeline focusing on clarity and polish.';
        } elseif (preg_match('/(meeting|interview|client|presentation|demo|call|sync|pitch|ជួប|ប្រជុំ|បទបង្ហាញ)/u', $combined)) {
            $detectedCategory = $detectedCategory ?: 'Work';
            $tags = ['meeting', 'communication'];
            $subtasks = $isKhmer ? [
                ['title' => 'រៀបចំរបៀបវារៈប្រជុំ (Meeting Agenda) និងឯកសារយោង', 'estimated_minutes' => 15],
                ['title' => 'ពិនិត្យសង្ខេបចំណុចសំខាន់ និងសំណួរដែលត្រូវសួរ', 'estimated_minutes' => 15],
                ['title' => 'ដំណើរការកិច្ចប្រជុំ និងកត់ត្រា Action Items សំខាន់ៗ', 'estimated_minutes' => 30],
                ['title' => 'ផ្ញើអ៊ីមែល Follow-up សង្ខេបការព្រមព្រៀង និងកាលវិភាគបន្ទាប់', 'estimated_minutes' => 15],
            ] : [
                ['title' => 'Draft meeting agenda & gather reference materials', 'estimated_minutes' => 15],
                ['title' => 'Review background context & key talking points', 'estimated_minutes' => 15],
                ['title' => 'Conduct meeting and record specific action items', 'estimated_minutes' => 30],
                ['title' => 'Send recap email with agreed next steps & deadlines', 'estimated_minutes' => 15],
            ];
            $summary = $isKhmer ? 'រៀបចំកិច្ចប្រជុំប្រកបដោយប្រសិទ្ធភាព និងច្បាស់លាស់។' : 'Ensure impactful meetings with clear takeaways and follow-up.';
        } elseif (preg_match('/(report|tax|finance|invoice|budget|audit|expense|revenue|quarterly|accounting|ហិរញ្ញវត្ថុ|ពន្ធ|របាយការណ៍)/u', $combined)) {
            $detectedCategory = $detectedCategory ?: 'Finance';
            $tags = ['finance', 'reporting'];
            $subtasks = $isKhmer ? [
                ['title' => 'ប្រមូល និងផ្ទៀងផ្ទាត់ទិន្នន័យចំណូល-ចំណាយ និងវិក្កយបត្រ', 'estimated_minutes' => 30],
                ['title' => 'គណនាតួលេខ និងផ្ទៀងផ្ទាត់សមតុល្យគណនី (Reconcile balances)', 'estimated_minutes' => 45],
                ['title' => 'រៀបចំសេចក្តីព្រាងរបាយការណ៍ និងក្រាហ្វិកសង្ខេប', 'estimated_minutes' => 30],
                ['title' => 'ពិនិត្យចុងក្រោយ និងបញ្ជូនទៅកាន់អ្នកពាក់ព័ន្ធ', 'estimated_minutes' => 15],
            ] : [
                ['title' => 'Collect and organize transaction receipts & source data', 'estimated_minutes' => 30],
                ['title' => 'Reconcile ledger balances & calculate key metrics', 'estimated_minutes' => 45],
                ['title' => 'Draft financial summary report with breakdown charts', 'estimated_minutes' => 30],
                ['title' => 'Final review for compliance & deliver to stakeholders', 'estimated_minutes' => 15],
            ];
            $summary = $isKhmer ? 'ជំហានច្បាស់លាស់សម្រាប់របាយការណ៍ហិរញ្ញវត្ថុត្រឹមត្រូវ។' : 'Structured financial review ensuring accuracy and compliance.';
        } elseif (preg_match('/(study|learn|read|book|exam|course|chapter|research|រៀន|អាន|ប្រឡង|ស្រាវជ្រាវ)/u', $combined)) {
            $detectedCategory = $detectedCategory ?: 'Study';
            $tags = ['learning', 'knowledge'];
            $subtasks = $isKhmer ? [
                ['title' => 'មើលទិដ្ឋភាពទូទៅនៃមេរៀន និងកំណត់ចំណុចស្នូល', 'estimated_minutes' => 20],
                ['title' => 'អានយ៉ាងលម្អិត និងកត់ត្រាកំណត់ចំណាំសង្ខេប', 'estimated_minutes' => 40],
                ['title' => 'អនុវត្តលំហាត់ជាក់ស្តែង ឬអនុវត្តគំរូសាកល្បង', 'estimated_minutes' => 35],
                ['title' => 'ធ្វើស្វ័យតេស្ត និងពិនិត្យចំណុចដែលនៅខ្វះខាត', 'estimated_minutes' => 20],
            ] : [
                ['title' => 'Scan syllabus overview & identify key core concepts', 'estimated_minutes' => 20],
                ['title' => 'Deep read material & write structured summary notes', 'estimated_minutes' => 40],
                ['title' => 'Complete hands-on practice problems or flashcards', 'estimated_minutes' => 35],
                ['title' => 'Self-quiz to test retention & review difficult areas', 'estimated_minutes' => 20],
            ];
            $summary = $isKhmer ? 'វិធីសាស្ត្រសិក្សាផ្អែកលើ Active Recall និងការអនុវត្ត។' : 'Study workflow optimized for high retention and active recall.';
        } else {
            // General high-utility decomposition
            $detectedCategory = $detectedCategory ?: 'Work';
            $tags = ['action-plan'];
            $subtasks = $isKhmer ? [
                ['title' => 'ស្រាវជ្រាវ និងកំណត់វិសាលភាពលម្អិតនៃកិច្ចការ', 'estimated_minutes' => 15],
                ['title' => 'អនុវត្តជំហានស្នូល និងលទ្ធផលចម្បង', 'estimated_minutes' => 45],
                ['title' => 'ត្រួតពិនិត្យគុណភាព និងផ្ទៀងផ្ទាត់ភាពត្រឹមត្រូវ', 'estimated_minutes' => 20],
                ['title' => 'បញ្ចប់ និងកត់ត្រាលទ្ធផលជោគជ័យ', 'estimated_minutes' => 10],
            ] : [
                ['title' => 'Define scope & prepare required prerequisites', 'estimated_minutes' => 15],
                ['title' => 'Execute core implementation deliverables', 'estimated_minutes' => 45],
                ['title' => 'Review outcome, quality check & verify completeness', 'estimated_minutes' => 20],
                ['title' => 'Finalize documentation & mark milestone achieved', 'estimated_minutes' => 10],
            ];
            $summary = $isKhmer ? 'ផែនការ ៤ ជំហានសម្រេចកិច្ចការប្រកបដោយប្រសិទ្ធភាព។' : 'Practical 4-phase execution plan for maximum momentum.';
        }

        $formattedSubtasks = [];
        $totalMinutes = 0;
        foreach ($subtasks as $i => $item) {
            $formattedSubtasks[] = [
                'id' => 'ai_' . ($i + 1) . '_' . substr(md5($title . $i), 0, 6),
                'title' => $item['title'],
                'completed' => false,
                'estimated_minutes' => $item['estimated_minutes'],
            ];
            $totalMinutes += $item['estimated_minutes'];
        }

        return [
            'success' => true,
            'source' => 'smart_heuristic',
            'subtasks' => $formattedSubtasks,
            'suggested_priority' => $priority,
            'suggested_category' => $detectedCategory,
            'estimated_minutes' => $totalMinutes,
            'suggested_tags' => $tags,
            'summary' => $summary,
        ];
    }

    /**
     * Smart heuristic enhancement for titles and descriptions
     */
    protected function smartHeuristicEnhance(string $title, ?string $description, string $lang): array
    {
        $isKhmer = ($lang === 'km');
        $cleanTitle = trim($title);

        $category = $this->detectCategory($cleanTitle);
        $priority = 'medium';

        if (preg_match('/(urgent|asap|critical|fix|bug|broken|error|បន្ទាន់)/i', $cleanTitle)) {
            $priority = 'high';
        }

        // Polish title casing and structure
        $polishedTitle = ucfirst($cleanTitle);
        if (!preg_match('/[.!?]$/', $polishedTitle)) {
            // Keep action title punchy without period
        }

        $enhancedDescription = $description;
        if (empty($enhancedDescription)) {
            $enhancedDescription = $isKhmer
                ? "🎯 គោលបំណង៖ សម្រេចកិច្ចការ {$cleanTitle} ដោយជោគជ័យ។\n\n📌 លទ្ធផលរំពឹងទុក៖\n- បញ្ចប់ជំហានដែលបានគ្រោងទុកឱ្យទាន់ពេល\n- ផ្ទៀងផ្ទាត់គុណភាព និងភាពត្រឹមត្រូវ\n- រាយការណ៍លទ្ធផល"
                : "🎯 Objective: Successfully complete {$cleanTitle}.\n\n📌 Deliverables & Definition of Done:\n- Core deliverables executed to high standard\n- Quality checked and verified\n- Status updated upon completion";
        }

        return [
            'success' => true,
            'source' => 'smart_heuristic',
            'title' => $polishedTitle,
            'description' => $enhancedDescription,
            'suggested_category' => $category,
            'suggested_priority' => $priority,
            'suggested_tags' => [strtolower($category)],
        ];
    }

    /**
     * Auto-detect category from keywords
     */
    protected function detectCategory(string $text): string
    {
        $lower = mb_strtolower($text);

        if (preg_match('/(code|bug|api|test|feature|database|git|deploy|server|sql|app|front|back|auth|docker|laravel|flutter)/u', $lower)) {
            return 'Dev';
        }
        if (preg_match('/(design|ui|ux|figma|logo|banner|color|layout|poster|mockup)/u', $lower)) {
            return 'Design';
        }
        if (preg_match('/(tax|invoice|finance|budget|salary|payment|bank|crypto|cost|money|dollar)/u', $lower)) {
            return 'Finance';
        }
        if (preg_match('/(study|learn|book|read|exam|university|course|homework|school)/u', $lower)) {
            return 'Study';
        }
        if (preg_match('/(urgent|asap|emergency|crisis|danger|critical)/u', $lower)) {
            return 'Urgent';
        }
        if (preg_match('/(gym|workout|health|doctor|grocer|clean|home|family|dinner|cook|buy|call)/u', $lower)) {
            return 'Personal';
        }

        return 'Work';
    }

    /**
     * Interactive Chatbot: Handles user conversation, action execution, and task queries.
     */
    public function chat(User $user, string $message, string $lang = 'en'): array
    {
        $message = trim($message);
        $isKhmer = ($lang === 'km');

        // 1. Persist user message
        $userMsg = AiChatMessage::create([
            'user_id' => $user->id,
            'role' => 'user',
            'message' => $message,
        ]);

        // 2. Fetch user tasks context
        $tasks = $user->tasks()->get();
        $total = $tasks->count();
        $completed = $tasks->where('status', 'completed')->count();
        $inProgress = $tasks->where('status', 'in_progress')->values();
        $pending = $tasks->where('status', 'pending')->values();
        $overdue = $tasks->filter->is_overdue->values();
        $highPriority = $tasks->where('priority', 'high')->where('status', '!=', 'completed')->values();
        $dueToday = $tasks->filter(fn ($t) => $t->due_date && $t->due_date->isToday() && $t->status !== 'completed')->values();

        $actionType = null;
        $actionData = null;
        $reply = null;

        // 3. Direct Intent: Task Creation
        // e.g. "create task: Review financial report #Finance !high by tomorrow"
        if (preg_match('/^(?:create\s+task|add\s+task|new\s+task|task|create|បង្កើតកិច្ចការ|បន្ថែម)(?:\s*[:\-]\s*|\s+)(.+)$/iu', $message, $m) ||
            preg_match('/^(?:please\s+)?(?:create|add)\s+(?:a\s+)?(?:new\s+)?task\s+(?:called\s+|to\s+|for\s+)?(.+)$/i', $message, $m)) {
            $taskInput = trim($m[1]);
            if (!empty($taskInput)) {
                $parsed = $this->parseNlp($taskInput);
                $task = Task::create([
                    'user_id' => $user->id,
                    'title' => $parsed['title'],
                    'priority' => $parsed['priority'] ?? 'medium',
                    'category' => $parsed['category'] ?? 'Work',
                    'due_date' => $parsed['due_date'] ?? null,
                    'tags' => $parsed['tags'] ?? [],
                    'status' => 'pending',
                    'description' => 'Created via AI Task Copilot Chatbot.',
                ]);

                $actionType = 'task_created';
                $actionData = [
                    'task_id' => $task->id,
                    'title' => $task->title,
                    'priority' => $task->priority,
                    'category' => $task->category,
                    'due_date' => $task->due_date?->format('M d, Y'),
                ];

                if ($isKhmer) {
                    $reply = "✅ បានបង្កើតកិច្ចការថ្មី **{$task->title}** ដោយជោគជ័យ!\n\n"
                        . "• **កម្រិតអាទិភាព**៖ " . strtoupper($task->priority) . "\n"
                        . "• **ប្រភេទ**៖ {$task->category}\n"
                        . ($task->due_date ? "• **ថ្ងៃផុតកំណត់**៖ " . $task->due_date->format('M d, Y') . "\n" : "")
                        . "\n💡 អ្នកអាចសួរខ្ញុំថា: *\"Breakdown {$task->title}\"* ដើម្បីឱ្យខ្ញុំរៀបចំបញ្ជីជំហានអនុវត្ត (Subtasks)!";
                } else {
                    $reply = "✅ Task **{$task->title}** created successfully!\n\n"
                        . "• **Priority**: " . strtoupper($task->priority) . "\n"
                        . "• **Category**: {$task->category}\n"
                        . ($task->due_date ? "• **Due Date**: " . $task->due_date->format('M d, Y') . "\n" : "")
                        . "\n💡 Tip: Say *\"Breakdown {$task->title}\"* to generate an actionable subtask checklist!";
                }
            }
        }

        // 4. Direct Intent: Task Breakdown / Checklist
        if (!$reply && (preg_match('/^(?:breakdown|break\s+down|checklist\s+for|how\s+to|បំបែក|របៀបធ្វើ)(?:\s*[:\-]\s*|\s+)(.+)$/iu', $message, $m))) {
            $topic = trim($m[1]);
            $breakdownResult = $this->breakdown($topic, null, null, $lang);
            $subtasks = $breakdownResult['subtasks'] ?? [];

            $actionType = 'task_breakdown';
            $actionData = [
                'subtasks' => $subtasks,
                'summary' => $breakdownResult['summary'] ?? null,
            ];

            if ($isKhmer) {
                $reply = "📋 **ផែនការបំបែកកិច្ចការ៖ {$topic}**\n\n";
                foreach ($subtasks as $idx => $st) {
                    $reply .= ($idx + 1) . ". **{$st['title']}** (" . ($st['estimated_minutes'] ?? 20) . " នាទី)\n";
                }
                if (!empty($breakdownResult['summary'])) {
                    $reply .= "\n💡 *{$breakdownResult['summary']}*";
                }
            } else {
                $reply = "📋 **Actionable Breakdown for: {$topic}**\n\n";
                foreach ($subtasks as $idx => $st) {
                    $reply .= ($idx + 1) . ". **{$st['title']}** (" . ($st['estimated_minutes'] ?? 20) . " mins)\n";
                }
                if (!empty($breakdownResult['summary'])) {
                    $reply .= "\n💡 *{$breakdownResult['summary']}*";
                }
            }
        }

        // 5. Direct Intent: Overdue Tasks Query
        if (!$reply && preg_match('/\b(overdue|late|behind|ហួសកាលកំណត់|ហួសពេល)\b/iu', $message)) {
            $actionType = 'overdue_summary';
            $actionData = [
                'count' => $overdue->count(),
                'tasks' => $overdue->map(fn ($t) => ['id' => $t->id, 'title' => $t->title, 'priority' => $t->priority])->take(5)->all(),
            ];

            if ($overdue->isEmpty()) {
                $reply = $isKhmer
                    ? "🎉 **អស្ចារ្យណាស់!** អ្នកមិនមានកិច្ចការណាដែលហួសកាលកំណត់នៅពេលនេះទេ។ កិច្ចការទាំងអស់ស្ថិតក្នុងលំហូរល្អប្រសើរ!"
                    : "🎉 **Fantastic!** You don't have any overdue tasks right now. Everything is right on track!";
            } else {
                if ($isKhmer) {
                    $reply = "⚠️ **អ្នកមាន {$overdue->count()} កិច្ចការហួសកាលកំណត់៖**\n\n";
                    foreach ($overdue->take(5) as $idx => $t) {
                        $reply .= ($idx + 1) . ". **{$t->title}** (ផុតកាលកំណត់៖ " . ($t->due_date ? $t->due_date->format('M d') : 'កន្លងមក') . ")\n";
                    }
                    $reply .= "\n👉 *អនុសាសន៍៖ ដោះស្រាយកិច្ចការ \"{$overdue->first()->title}\" ជាបន្ទាន់ដើម្បីការពារកុំឱ្យស្ទះការងារ!*";
                } else {
                    $reply = "⚠️ **You have {$overdue->count()} overdue task" . ($overdue->count() > 1 ? 's' : '') . ":**\n\n";
                    foreach ($overdue->take(5) as $idx => $t) {
                        $reply .= ($idx + 1) . ". **{$t->title}** (Due: " . ($t->due_date ? $t->due_date->format('M d') : 'Past') . ")\n";
                    }
                    $reply .= "\n👉 *Recommendation: Tackle \"{$overdue->first()->title}\" first to unblock your workflow!*";
                }
            }
        }

        // 6. Direct Intent: Standup / Daily Briefing
        if (!$reply && preg_match('/\b(standup|briefing|summary|status|today|progress|សង្ខេប|របាយការណ៍|ថ្ងៃនេះ)\b/iu', $message)) {
            $brief = $this->generateStandupBrief($tasks, $lang);
            $actionType = 'standup_summary';
            $actionData = ['brief' => $brief];

            if ($isKhmer) {
                $reply = "☀️ **សង្ខេបការងារប្រចាំថ្ងៃ (AI Standup)**\n\n"
                    . "**{$brief['headline']}**\n\n"
                    . "{$brief['summary']}\n\n"
                    . "• ហួសកាលកំណត់៖ **{$brief['overdue_count']}**\n"
                    . "• អាទិភាពខ្ពស់៖ **{$brief['urgent_count']}**\n"
                    . "• ដល់កំណត់ថ្ងៃនេះ៖ **{$brief['due_today_count']}**\n\n"
                    . "🎯 **អនុសាសន៍**៖ {$brief['action_recommendation']}";
            } else {
                $reply = "☀️ **Daily AI Standup Briefing**\n\n"
                    . "**{$brief['headline']}**\n\n"
                    . "{$brief['summary']}\n\n"
                    . "• Overdue: **{$brief['overdue_count']}**\n"
                    . "• High Priority: **{$brief['urgent_count']}**\n"
                    . "• Due Today: **{$brief['due_today_count']}**\n\n"
                    . "🎯 **Action Recommendation**: {$brief['action_recommendation']}";
            }
        }

        // 7. General Conversational / LLM Chat
        if (!$reply) {
            $apiKey = config('services.gemini.key');
            if (!empty($apiKey)) {
                $geminiReply = $this->callGeminiForChat($apiKey, $message, $user, $tasks, $lang);
                if (!empty($geminiReply)) {
                    $reply = $geminiReply;
                }
            }

            // Fallback to heuristic chat if Gemini is offline or unavailable
            if (!$reply) {
                $reply = $this->smartHeuristicChat($message, $user, $tasks, $lang);
            }
        }

        // 8. Persist assistant reply
        $botMsg = AiChatMessage::create([
            'user_id' => $user->id,
            'role' => 'assistant',
            'message' => $reply,
            'action_type' => $actionType,
            'action_data' => $actionData,
        ]);

        return [
            'success' => true,
            'message' => $reply,
            'action_type' => $actionType,
            'action_data' => $actionData,
            'message_id' => $botMsg->id,
            'created_at' => $botMsg->created_at->format('h:i A'),
        ];
    }

    /**
     * Gemini LLM Call for Conversational Task Chatbot
     */
    protected function callGeminiForChat(string $apiKey, string $message, User $user, Collection $tasks, string $lang): ?string
    {
        $model = config('services.gemini.model', 'gemini-2.5-flash');
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $langName = ($lang === 'km') ? 'Khmer' : 'English';
        $total = $tasks->count();
        $completed = $tasks->where('status', 'completed')->count();
        $overdue = $tasks->filter->is_overdue->values();
        $highPriority = $tasks->where('priority', 'high')->where('status', '!=', 'completed')->values();
        $inProgress = $tasks->where('status', 'in_progress')->values();

        $overdueTitles = $overdue->pluck('title')->take(4)->implode(', ') ?: 'None';
        $urgentTitles = $highPriority->pluck('title')->take(4)->implode(', ') ?: 'None';
        $inProgressTitles = $inProgress->pluck('title')->take(4)->implode(', ') ?: 'None';

        $systemPrompt = <<<SYS
You are Gemini, a helpful, clear, and friendly AI task assistant embedded inside the user's Task Manager web application.
User's Name: "{$user->name}"

Current Tasks Context:
- Total tasks: {$total} (Completed: {$completed})
- Overdue tasks ({$overdue->count()}): {$overdueTitles}
- High priority pending ({$highPriority->count()}): {$urgentTitles}
- In progress tasks ({$inProgress->count()}): {$inProgressTitles}

Guidelines:
- Answer in {$langName} language.
- Keep your responses clean, polite, easy to understand, and directly helpful.
- Avoid technical jargon, system architecture details, or overwhelming text dumps unless the user explicitly asks for technical details.
- Help the user organize, prioritize, and execute their tasks effectively.
- Inform the user gently that they can create tasks directly (e.g., `create task: Plan project launch by Friday !high`) or break tasks into subtasks (e.g., `breakdown: Redesign landing page`).
SYS;

        // Fetch recent messages for conversational context
        $recentHistory = AiChatMessage::where('user_id', $user->id)
            ->latest('id')
            ->take(6)
            ->get()
            ->reverse();

        $contents = [];
        foreach ($recentHistory as $msg) {
            $contents[] = [
                'role' => ($msg->role === 'assistant') ? 'model' : 'user',
                'parts' => [
                    ['text' => $msg->message],
                ],
            ];
        }

        // Append current message
        $contents[] = [
            'role' => 'user',
            'parts' => [
                ['text' => $message],
            ],
        ];

        // Clean contents so roles alternate user -> model -> user
        $sanitizedContents = [];
        $lastRole = null;
        foreach ($contents as $turn) {
            if ($turn['role'] === $lastRole) {
                $lastIndex = count($sanitizedContents) - 1;
                $sanitizedContents[$lastIndex]['parts'][0]['text'] .= "\n" . $turn['parts'][0]['text'];
            } else {
                $sanitizedContents[] = $turn;
                $lastRole = $turn['role'];
            }
        }
        if (!empty($sanitizedContents) && $sanitizedContents[0]['role'] !== 'user') {
            array_shift($sanitizedContents);
        }
        if (empty($sanitizedContents)) {
            $sanitizedContents[] = [
                'role' => 'user',
                'parts' => [['text' => $message]],
            ];
        }

        try {
            $response = Http::timeout(10)->post($endpoint, [
                'system_instruction' => [
                    'parts' => [
                        ['text' => $systemPrompt],
                    ],
                ],
                'contents' => $sanitizedContents,
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 850,
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $reply = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                if (!empty($reply)) {
                    return trim($reply);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Gemini Chat API call failed, falling back to smart heuristic: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Domain-aware smart heuristic chat when offline or without API key.
     */
    protected function smartHeuristicChat(string $message, User $user, Collection $tasks, string $lang): string
    {
        $isKhmer = ($lang === 'km');
        $lower = mb_strtolower($message);

        // Greetings
        if (preg_match('/^(hi|hello|hey|good\s+morning|good\s+afternoon|good\s+evening|សួស្តី|ជម្រាបសួរ)/iu', $lower)) {
            if ($isKhmer) {
                return "👋 សួស្តី **{$user->name}**! ខ្ញុំជា Gemini ជំនួយការ AI របស់អ្នក។\n\nខ្ញុំអាចជួយអ្នកបង្កើតកិច្ចការ រៀបចំជំហានការងារ ឬពិនិត្យកាលបរិច្ឆេទថ្ងៃនេះ។\n\nតើខ្ញុំអាចជួយអ្វីដល់អ្នកនៅថ្ងៃនេះ?";
            }
            return "👋 Hello **{$user->name}**! I'm Gemini, your AI task assistant.\n\nI can help you create tasks, break down goals, or check what needs your attention today.\n\nHow can I help you today?";
        }

        // About Gemini / Model Info
        if (preg_match('/(it\s+spec|it\s+info|stack|architecture|gemini|system\s+spec|model\s+spec|model|tech\s+stack|បច្ចេកវិទ្យា|ម៉ូដែល)/iu', $lower)) {
            if ($isKhmer) {
                return "✨ ខ្ញុំជា **Google Gemini** ជំនួយការ AI ដើម្បីជួយអ្នកគ្រប់គ្រង និងតាមដានកិច្ចការងារយ៉ាងរហ័ស និងមានប្រសិទ្ធភាព។";
            }
            return "✨ I am powered by **Google Gemini** models. I help you stay organized, prioritize goals, and manage your tasks effortlessly.";
        }

        // Bug / Issue triage
        if (preg_match('/(bug|error|exception|crash|500|404|fix\s+bug|debug|បញ្ហា)/iu', $lower)) {
            if ($isKhmer) {
                return "🐛 ខ្ញុំអាចជួយអ្នករៀបចំកិច្ចការសម្រាប់ដោះស្រាយបញ្ហានេះបាន! អ្នកអាចបង្កើតកិច្ចការដោយសរសេរ៖\n`create task: ដោះស្រាយបញ្ហា error !high`";
            }
            return "🐛 I can help you track and organize this fix! You can quickly add it by typing:\n`create task: Fix issue and test resolution !high`";
        }

        // Productivity tips / Pomodoro
        if (preg_match('/(tip|advice|focus|productivity|pomodoro|procrastin|time|វិធី|គន្លឹះ|ផ្តោត|ពេលវេលា)/iu', $lower)) {
            if ($isKhmer) {
                return "💡 **គន្លឹះបង្កើនផលិតភាពការងារ៖**\n\n"
                    . "1. 🍅 **Pomodoro**៖ ផ្តោតលើការងារ ២៥ នាទី រួចសម្រាក ៥ នាទី។\n"
                    . "2. 🐸 **Eat The Frog**៖ បញ្ចប់កិច្ចការដែលសំខាន់បំផុតមុនគេនៅពេលព្រឹក។\n"
                    . "3. ⚡ **ក្បួន ២ នាទី**៖ ប្រសិនបើកិច្ចការចំណាយពេលតិចជាង ២ នាទី ចូរធ្វើវាភ្លាមៗ។\n\n"
                    . "តើអ្នកចង់ឱ្យខ្ញុំជួយរៀបចំកិច្ចការបន្ទាប់របស់អ្នកដែរឬទេ?";
            }
            return "💡 **Top 3 Productivity Tips:**\n\n"
                . "1. 🍅 **Pomodoro**: Focus deeply for 25 minutes, then take a short 5-minute break.\n"
                . "2. 🐸 **Eat That Frog**: Tackle your highest priority task first thing in the morning.\n"
                . "3. ⚡ **2-Minute Rule**: If a task takes less than 2 minutes, complete it right away.\n\n"
                . "Would you like me to help you organize your next task?";
        }

        // Help / capabilities
        if (preg_match('/(help|command|what\s+can\s+you\s+do|feature|ជួយ|សមត្ថភាព)/iu', $lower)) {
            if ($isKhmer) {
                return "🤖 **ខ្ញុំអាចជួយអ្នកលើការងារដូចជា៖**\n\n"
                    . "• `create task: <ឈ្មោះកិច្ចការ> [!high/medium/low]` ដើម្បីបង្កើតកិច្ចការថ្មី\n"
                    . "• `breakdown: <កិច្ចការ>` ដើម្បីបំបែកជាជំហានតូចៗ\n"
                    . "• `overdue` ដើម្បីពិនិត្យកិច្ចការហួសកំណត់\n"
                    . "• `standup` ដើម្បីសង្ខេបការងារប្រចាំថ្ងៃ\n\n"
                    . "តើអ្នកចង់ចាប់ផ្តើមជាមួយអ្វីដែរ?";
            }
            return "🤖 **Here is how I can help:**\n\n"
                . "• `create task: <task title> [!high/medium/low]` to add a new task\n"
                . "• `breakdown: <task>` to generate action steps\n"
                . "• `overdue` to see delayed items\n"
                . "• `standup` for a quick daily summary\n\n"
                . "How can I assist you right now?";
        }

        // Thank you
        if (preg_match('/(thank|thanks|អរគុណ)/iu', $lower)) {
            return $isKhmer
                ? "😊 មិនអីទេ! ខ្ញុំរីករាយជានិច្ចក្នុងការជួយអ្នក។"
                : "😊 You're very welcome! Let me know if you need anything else.";
        }

        // Default clean fallback
        if ($isKhmer) {
            return "🤖 ខ្ញុំបានទទួលសាររបស់អ្នក! អ្នកអាចសួរខ្ញុំឱ្យបង្កើតកិច្ចការ (`create task: ...`), បំបែកជំហាន (`breakdown: ...`), ឬពិនិត្យកាលវិភាគ (`standup` ឬ `overdue`)។ តើខ្ញុំអាចជួយអ្វីបាន?";
        }

        return "🤖 I'm here to help with your tasks! You can ask me to create a task (`create task: ...`), break down a goal (`breakdown: ...`), or check your schedule (`standup` or `overdue`). How can I help?";
    }

    /**
     * Get user's recent chat history
     */
    public function getChatHistory(User $user, int $limit = 40): Collection
    {
        return AiChatMessage::where('user_id', $user->id)
            ->latest('id')
            ->take($limit)
            ->get()
            ->reverse()
            ->values();
    }

    /**
     * Clear user's chat history
     */
    public function clearChatHistory(User $user): int
    {
        return AiChatMessage::where('user_id', $user->id)->delete();
    }
}

