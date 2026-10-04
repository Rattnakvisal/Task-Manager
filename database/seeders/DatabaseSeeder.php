<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $defaultUser = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $users = User::all();
        foreach ($users as $user) {
            if (Task::where('user_id', $user->id)->count() === 0) {
                Task::create([
                    'user_id' => $user->id,
                    'title' => 'Launch Task Manager Workspace V2',
                    'description' => 'Finalize new responsive UI, dark mode polish, and AI copilot integration.',
                    'priority' => 'high',
                    'status' => 'in_progress',
                    'category' => 'Work',
                    'due_date' => now()->addDays(2),
                    'is_pinned' => true,
                    'subtasks' => [
                        ['id' => 'st-1', 'title' => 'Design 3D Productivity Hero Banner', 'completed' => true],
                        ['id' => 'st-2', 'title' => 'AI Copilot Subtask Generator', 'completed' => true],
                        ['id' => 'st-3', 'title' => 'Review Weekly Activity Metrics', 'completed' => false],
                    ],
                ]);

                Task::create([
                    'user_id' => $user->id,
                    'title' => 'Design System & Typography Polish',
                    'description' => 'Harmonize sidebar gradients, card borders, and Khmer Kantumruy Pro fonts.',
                    'priority' => 'medium',
                    'status' => 'completed',
                    'category' => 'Design',
                    'due_date' => now()->subDay(),
                    'is_pinned' => false,
                    'subtasks' => [
                        ['id' => 'st-4', 'title' => 'Import Google Fonts', 'completed' => true],
                        ['id' => 'st-5', 'title' => 'Dark theme gradient tokens', 'completed' => true],
                    ],
                ]);

                Task::create([
                    'user_id' => $user->id,
                    'title' => 'Client Demo & Standup Walkthrough',
                    'description' => 'Present the interactive dashboard and smart task features to stakeholders.',
                    'priority' => 'high',
                    'status' => 'pending',
                    'category' => 'Urgent',
                    'due_date' => now()->addDays(3),
                    'is_pinned' => true,
                    'subtasks' => [
                        ['id' => 'st-6', 'title' => 'Prepare feature summary', 'completed' => false],
                        ['id' => 'st-7', 'title' => 'Walkthrough live demo', 'completed' => false],
                    ],
                ]);

                Task::create([
                    'user_id' => $user->id,
                    'title' => 'Sprint Retrospective & Code Review',
                    'description' => 'Review completed deliverables and plan next sprint milestones.',
                    'priority' => 'low',
                    'status' => 'completed',
                    'category' => 'Study',
                    'due_date' => now()->subDays(2),
                    'is_pinned' => false,
                    'subtasks' => [
                        ['id' => 'st-8', 'title' => 'Merge branch pull requests', 'completed' => true],
                    ],
                ]);
            }
        }
    }
}

