<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Profiles that predate the onboarding feature belong to existing users.
        // Mark them complete so only accounts registered after this deployment
        // are sent through the new-user flow.
        DB::table('user_profiles')
            ->whereNull('onboarding_completed_at')
            ->update(['onboarding_completed_at' => now()]);
    }

    public function down(): void
    {
        // This data backfill is intentionally irreversible. Resetting completion
        // would unexpectedly send existing accounts through onboarding.
    }
};
