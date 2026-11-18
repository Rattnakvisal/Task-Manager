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
                    'title' => 'Laravel API',
                    'description' => 'Build high-performance RESTful API endpoints with Sanctum authentication and PostgreSQL.',
                    'priority' => 'high',
                    'status' => 'in_progress',
                    'category' => 'Work',
                    'due_date' => now()->addDays(2),
                    'is_pinned' => true,
                    'subtasks' => [
                        ['id' => 'st-1', 'title' => 'Setup API routes and middleware', 'completed' => true],
                        ['id' => 'st-2', 'title' => 'Implement Sanctum token authentication', 'completed' => true],
                        ['id' => 'st-3', 'title' => 'Connect Supabase PostgreSQL pooler', 'completed' => false],
                    ],
                ]);

                Task::create([
                    'user_id' => $user->id,
                    'title' => 'Spring Boot API',
                    'description' => 'Develop enterprise microservices backend with Spring Data JPA and Spring Security.',
                    'priority' => 'medium',
                    'status' => 'pending',
                    'category' => 'Study',
                    'due_date' => now()->addDays(5),
                    'is_pinned' => false,
                    'subtasks' => [
                        ['id' => 'st-4', 'title' => 'Configure Spring Security & JWT filter', 'completed' => false],
                        ['id' => 'st-5', 'title' => 'Build REST controllers for task management', 'completed' => false],
                        ['id' => 'st-6', 'title' => 'Write unit and integration tests', 'completed' => false],
                    ],
                ]);

                Task::create([
                    'user_id' => $user->id,
                    'title' => 'Advance Flutter',
                    'description' => 'Master advanced Flutter architecture, state management with InheritedWidget, and custom animations.',
                    'priority' => 'high',
                    'status' => 'in_progress',
                    'category' => 'Study',
                    'due_date' => now()->addDays(1),
                    'is_pinned' => true,
                    'subtasks' => [
                        ['id' => 'st-7', 'title' => 'Implement iPhone-style UI components & buttons', 'completed' => true],
                        ['id' => 'st-8', 'title' => 'Optimize hero slideshow & streak animations', 'completed' => true],
                        ['id' => 'st-9', 'title' => 'Add offline-first secure session storage', 'completed' => false],
                    ],
                ]);

                Task::create([
                    'user_id' => $user->id,
                    'title' => 'Superbase',
                    'description' => 'Configure Supabase PostgreSQL database, connection pooling, and real-time subscriptions.',
                    'priority' => 'medium',
                    'status' => 'completed',
                    'category' => 'Personal',
                    'due_date' => now()->subDay(),
                    'is_pinned' => false,
                    'subtasks' => [
                        ['id' => 'st-10', 'title' => 'Create Supabase project & pooler connection', 'completed' => true],
                        ['id' => 'st-11', 'title' => 'Setup Row Level Security (RLS) policies', 'completed' => true],
                    ],
                ]);
            }
        }
    }
}

