<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQueryTable extends Migration
{
    public function up()
    {
        Schema::create('query', function (Blueprint $table) {
            $table->increments('id');

            $table->text('params')->nullable();
            $table->bigInteger('duration')->nullable();
            $table->string('page')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::drop('query');
    }
}
