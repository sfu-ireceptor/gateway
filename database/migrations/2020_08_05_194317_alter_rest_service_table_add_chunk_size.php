<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterRestServiceTableAddChunkSize extends Migration
{
    public function up()
    {
        Schema::table('rest_service', function ($table) {
            $table->integer('chunk_size')->nullable();
        });
    }

    public function down()
    {
        Schema::table('rest_service', function ($table) {
            $table->dropColumn('chunk_size');
        });
    }
}
