<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterDownloadTableAddFileSizeField extends Migration
{
    public function up()
    {
        Schema::table('download', function ($table) {
            $table->bigInteger('file_size')->nullable();
        });
    }

    public function down()
    {
        Schema::table('download', function ($table) {
            $table->dropColumn('file_size');
        });
    }
}
