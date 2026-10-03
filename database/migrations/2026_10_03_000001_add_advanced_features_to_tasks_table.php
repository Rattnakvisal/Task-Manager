<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('category')->nullable()->after('priority');
            $table->json('subtasks')->nullable()->after('description');
            $table->json('tags')->nullable()->after('category');
            $table->boolean('is_pinned')->default(false)->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['category', 'subtasks', 'tags', 'is_pinned']);
        });
    }
};
