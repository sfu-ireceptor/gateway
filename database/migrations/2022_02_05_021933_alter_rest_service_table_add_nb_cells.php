<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterRestServiceTableAddNbCells extends Migration
{
    public function up()
    {
        Schema::table('rest_service', function ($table) {
            $table->bigInteger('nb_cells')->default(0);
        });
    }

    public function down()
    {
        Schema::table('rest_service', function ($table) {
            $table->dropColumn('nb_cells');
        });
    }
}
