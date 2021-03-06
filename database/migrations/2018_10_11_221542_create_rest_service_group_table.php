<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRestServiceGroupTable extends Migration
{
    public function up()
    {
        Schema::create('rest_service_group', function (Blueprint $table) {
            $table->increments('id');

            $table->text('code');
            $table->text('name');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::drop('rest_service_group');
    }
}
