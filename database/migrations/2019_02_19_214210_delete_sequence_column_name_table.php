<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DeleteSequenceColumnNameTable extends Migration
{
    public function up()
    {
        Schema::drop('sequence_column_name');
    }

    public function down()
    {
        Schema::create('sequence_column_name', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('title');
            $table->boolean('enabled');
            $table->timestamps();
        });
    }
}
