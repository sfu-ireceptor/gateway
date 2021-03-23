<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterRestServiceTableAddAsyncField extends Migration
{
    public function up()
    {
        Schema::table('rest_service', function ($table) {
            $table->boolean('async')->default(false);
        });
    }

    public function down()
    {
        Schema::table('rest_service', function ($table) {
            $table->dropColumn('async');
        });
    }
}
