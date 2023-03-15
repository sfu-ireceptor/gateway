<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('user', function ($table) {
            $table->string('token')->nullable();
        });

        // copy current tokens if needed
        $affected = DB::update('update user set token = password where token is null');
    }

    public function down()
    {
        Schema::table('user', function ($table) {
            $table->dropColumn('token');
        });
    }
};
