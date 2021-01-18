<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterRestServiceTableAddStatsField extends Migration
{
    public function up()
    {
        Schema::table('rest_service', function ($table) {
            $table->boolean('stats')->default(false);
        });
    }

    public function down()
    {
        Schema::table('rest_service', function ($table) {
            $table->dropColumn('stats');
        });
    }
}
