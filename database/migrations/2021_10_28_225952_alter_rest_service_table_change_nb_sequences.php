<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AlterRestServiceTableChangeNbSequences extends Migration
{
    public function up()
    {
        Schema::table('rest_service', function ($table) {
            $table->bigInteger('nb_sequences')->nullable()->comment(' ')->change();
        });
    }

    public function down()
    {
        Schema::table('rest_service', function ($table) {
            $table->integer('nb_sequences')->nullable()->comment('')->change();
        });
    }
}
