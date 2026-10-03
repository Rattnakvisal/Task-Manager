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
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        if (Task::where('user_id', $user->id)->count() === 0) {
            Task::create([
                'user_id' => $user->id,
                'title' => 'រៀបចំគម្រោង Task Manager V2',
                'description' => 'រៀបចំ Roadmap សម្រាប់មុខងារថ្មីៗ និងការរចនា UI/UX ទំនើប។',
                'priority' => 'high',
                'status' => 'in_progress',
                'category' => 'work',
                'due_date' => now()->addDays(2),
                'is_pinned' => true,
                'subtasks' => [
                    ['id' => 'st-1', 'title' => 'រចនា Kanban Board Drag & Drop', 'completed' => true],
                    ['id' => 'st-2', 'title' => 'បង្កើត Command Palette (Ctrl+K)', 'completed' => true],
                    ['id' => 'st-3', 'title' => 'បន្ថែមរបាយការណ៍ Weekly Activity Chart', 'completed' => false],
                ],
            ]);

            Task::create([
                'user_id' => $user->id,
                'title' => 'Design System & Khmer Typography',
                'description' => 'Fine-tune Kantumruy Pro & Instrument Sans typography for maximum readability.',
                'priority' => 'medium',
                'status' => 'completed',
                'category' => 'design',
                'due_date' => now()->subDay(),
                'is_pinned' => false,
                'subtasks' => [
                    ['id' => 'st-4', 'title' => 'Import Google Fonts', 'completed' => true],
                    ['id' => 'st-5', 'title' => 'Add CSS custom variables', 'completed' => true],
                ],
            ]);

            Task::create([
                'user_id' => $user->id,
                'title' => 'Client Demo & Feedback Gathering',
                'description' => 'Present the new bilingual Task Manager workspace to the team.',
                'priority' => 'high',
                'status' => 'pending',
                'category' => 'urgent',
                'due_date' => now()->addDays(4),
                'is_pinned' => true,
                'subtasks' => [
                    ['id' => 'st-6', 'title' => 'Record walkthrough video', 'completed' => false],
                    ['id' => 'st-7', 'title' => 'Prepare release notes', 'completed' => false],
                ],
            ]);
        }
    }
}

