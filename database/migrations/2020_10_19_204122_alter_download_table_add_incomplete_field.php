<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AlterDownloadTableAddIncompleteField extends Migration
{
    public function up()
    {
        Schema::table('download', function ($table) {
            $table->boolean('incomplete')->default(false);
        });
    }

    public function down()
    {
        Schema::table('download', function ($table) {
            $table->dropColumn('incomplete');
        });
    }
}
