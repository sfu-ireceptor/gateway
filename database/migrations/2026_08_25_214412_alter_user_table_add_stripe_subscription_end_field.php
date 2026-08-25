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
        // Add date for when stripe subscription ends
        Schema::table('user', function ($table) {
            $table->date('stripe_subscription_end')->nullable();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user', function ($table) {
            $table->dropColumn('stripe_subscription_end');
        });
    }
};
