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
        // Add a status field to track the status of the user.
        // In preparation for having different levels of access.
        Schema::table('user', function ($table) {
            $table->text('status')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the field.
        Schema::table('user', function ($table) {
            $table->text('status')->nullable();
        });
    }
};
