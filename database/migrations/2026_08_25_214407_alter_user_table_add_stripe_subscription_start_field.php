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
        // Add date for when terms and conditions were accepted
        Schema::table('user', function ($table) {
            $table->date('stripe_subscription_start')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user', function ($table) {
            $table->dropColumn('stripe_subscription_start');
        });
    }
};
