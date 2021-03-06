<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSequenceColumnNameTable extends Migration
{
    public function up()
    {
        Schema::create('sequence_column_name', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('title');
            $table->boolean('enabled');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sequence_column_name');
    }
}
