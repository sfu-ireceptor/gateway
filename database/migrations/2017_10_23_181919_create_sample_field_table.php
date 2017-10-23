<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSampleFieldTable extends Migration
{
    public function up()
    {
        Schema::create('sample_field', function (Blueprint $table) {
            $table->increments('id');
            $table->string('key');
            $table->string('airr');
            $table->string('airr_full');
            $table->string('ir_v1');
            $table->string('ir_v2');
            $table->string('ir_full');
            $table->string('ir_short');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::drop('sample_field');
    }
}
