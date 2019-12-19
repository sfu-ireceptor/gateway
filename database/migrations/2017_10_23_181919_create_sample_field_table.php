<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSampleFieldTable extends Migration
{
    public function up()
    {
        Schema::create('sample_field', function (Blueprint $table) {
            $table->increments('id');

            $table->string('ir_id')->nullable();

            $table->string('ir_v1')->nullable();
            $table->string('ir_v2')->nullable();
            $table->string('ir_full')->nullable();
            $table->string('ir_short')->nullable();

            $table->string('airr')->nullable();
            $table->string('airr_full')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::drop('sample_field');
    }
}
