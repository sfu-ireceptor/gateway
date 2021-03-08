<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AlterDownloadTableAddDownloadIncompleteInfoField extends Migration
{
    public function up()
    {
        Schema::table('download', function ($table) {
            $table->text('incomplete_info')->nullable();
        });
    }

    public function down()
    {
        Schema::table('download', function ($table) {
            $table->dropColumn('incomplete_info');
        });
    }
}
