<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('headline', 160)->nullable();
            $table->text('bio')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('work_status', 40)->nullable();
            $table->string('job_title', 120)->nullable();
            $table->string('company', 120)->nullable();
            $table->string('industry', 120)->nullable();
            $table->string('study_status', 40)->nullable();
            $table->string('education_level', 60)->nullable();
            $table->string('institution', 160)->nullable();
            $table->string('field_of_study', 160)->nullable();
            $table->json('skills')->nullable();
            $table->json('interests')->nullable();
            $table->string('website')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
