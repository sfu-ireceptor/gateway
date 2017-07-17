<?php

use Illuminate\Database\Migrations\Migration;

class CreateGwJobTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gw_agave_job', function ($table) {
            $table->increments('id');
            $table->string('agave_id');
            $table->string('agave_status');
            $table->string('input_folder');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gw_agave_job');
    }
}
