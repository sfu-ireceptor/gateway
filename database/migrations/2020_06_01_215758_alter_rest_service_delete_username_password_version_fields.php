<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

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
