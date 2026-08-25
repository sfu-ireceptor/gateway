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
        //
        Schema::table('user', function ($table) {
            $table->text('stripe_customer')->nullable();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the field.
        Schema::table('user', function ($table) {
            $table->text('stripe_customer')->nullable();
        });
    }
};
