<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterDownloadTableAddHiddenField extends Migration
{
    public function up()
    {
        Schema::table('download', function ($table) {
            $table->boolean('hidden')->default(false);
        });
    }

    public function down()
    {
        Schema::table('download', function ($table) {
            $table->dropColumn('hidden');
        });
    }
}
