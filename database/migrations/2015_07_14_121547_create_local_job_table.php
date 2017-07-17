<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateLocalJobTable extends Migration
{
    public function up()
    {
        Schema::create('local_job', function (Blueprint $table) {
            $table->increments('id');
            $table->text('queue');
            $table->text('description');
            $table->text('status');
            $table->dateTime('submitted');
            $table->dateTime('start');
            $table->dateTime('end');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('local_job');
    }
}
