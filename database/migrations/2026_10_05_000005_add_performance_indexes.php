<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->index(['user_id', 'status', 'due_date'], 'tasks_user_status_due_index');
            $table->index(['user_id', 'priority', 'due_date'], 'tasks_user_priority_due_index');
            $table->index(['user_id', 'is_pinned', 'due_date', 'created_at'], 'tasks_user_board_order_index');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index(
                ['notifiable_type', 'notifiable_id', 'type', 'created_at'],
                'notifications_owner_type_created_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_owner_type_created_index');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('tasks_user_status_due_index');
            $table->dropIndex('tasks_user_priority_due_index');
            $table->dropIndex('tasks_user_board_order_index');
        });
    }
};
