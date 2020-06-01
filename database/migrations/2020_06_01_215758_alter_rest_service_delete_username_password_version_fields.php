<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AlterRestServiceDeleteUsernamePasswordVersionFields extends Migration
{
    public function up()
    {
        Schema::table('rest_service', function ($table) {
            $table->dropColumn('username');
            $table->dropColumn('password');
            $table->dropColumn('version');
        });
    }

    public function down()
    {
    }
}
