<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add date for when the user's email was last confirmed
        Schema::table('user', function ($table) {
            $table->date('privacy_policy_accepted_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('user', function ($table) {
            $table->dropColumn('privacy_policy_accepted_date');
        });
    }
};
