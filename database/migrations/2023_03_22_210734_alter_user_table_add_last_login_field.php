<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('user', function ($table) {
            $table->dateTime('last_login')->nullable();
        });

        // copy updated_at to last_login because that's how
        // we have been tracking last login until now
        $affected = DB::update('update user set last_login = updated_at');
    }

    public function down()
    {
        Schema::table('last_login', function ($table) {
            $table->dropColumn('token');
        });
    }
};
