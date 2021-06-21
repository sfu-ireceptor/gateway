<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterRestServiceTableAddNbClones extends Migration
{
    public function up()
    {
        Schema::table('rest_service', function ($table) {
            $table->integer('nb_clones')->default(0);
        });
    }

    public function down()
    {
        Schema::table('rest_service', function ($table) {
            $table->dropColumn('nb_clones');
        });
    }
}
