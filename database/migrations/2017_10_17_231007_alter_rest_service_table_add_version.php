<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AlterRestServiceTableAddVersion extends Migration
{
    public function up()
    {
        Schema::table('rest_service', function ($table) {
            $table->integer('version')->default(1);
        });
    }

    public function down()
    {
        Schema::table('rest_service', function ($table) {
            $table->dropColumn('version');
        });
    }
}
