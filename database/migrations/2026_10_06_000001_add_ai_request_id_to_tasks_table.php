<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('ai_request_id', 64)->nullable()->after('user_id');
            $table->unique(['user_id', 'ai_request_id'], 'tasks_user_ai_request_unique');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropUnique('tasks_user_ai_request_unique');
            $table->dropColumn('ai_request_id');
        });
    }
};
