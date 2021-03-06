<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AlterDownloadTableAddQueryLogIdField extends Migration
{
    public function up()
    {
        Schema::table('download', function ($table) {
            $table->text('query_log_id')->nullable();
        });
    }

    public function down()
    {
        Schema::table('download', function ($table) {
            $table->dropColumn('query_log_id');
        });
    }
}
