<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('user', function ($table) {
            $table->text('notes')->nullable();
        });
    }

    public function down()
    {
        Schema::table('user', function ($table) {
            $table->dropColumn('notes');
        });
    }
};
