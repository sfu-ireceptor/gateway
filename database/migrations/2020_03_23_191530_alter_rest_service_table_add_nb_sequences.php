<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterRestServiceTableAddNbSequences extends Migration
{
    public function up()
    {
        Schema::table('rest_service', function ($table) {
            $table->integer('nb_samples');
            $table->integer('nb_sequences');
            $table->dateTime('last_cached')->nullable()->default(null);
        });
    }

    public function down()
    {
        Schema::table('rest_service', function ($table) {
            $table->dropColumn('nb_samples');
            $table->dropColumn('nb_sequences');
            $table->dropColumn('last_cached');
        });
    }
}
