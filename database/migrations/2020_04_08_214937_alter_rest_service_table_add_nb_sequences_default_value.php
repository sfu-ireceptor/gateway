<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterRestServiceTableAddNbSequencesDefaultValue extends Migration
{
    public function up()
    {
        Schema::table('rest_service', function ($table) {
            $table->integer('nb_samples')->default(0)->change();
            $table->integer('nb_sequences')->default(0)->change();
        });
    }

    public function down()
    {
        Schema::table('rest_service', function ($table) {
        });
    }
}
