<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterRestServiceTableAddApiVersion extends Migration
{
    public function up()
    {
        Schema::table('rest_service', function ($table) {
            $table->text('api_version')->nullable();
        });
    }

    public function down()
    {
        Schema::table('rest_service', function ($table) {
            $table->dropColumn('api_version');
        });
    }
}
