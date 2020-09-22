<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

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
