<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeploymentTable extends Migration
{
    public function up()
    {
        Schema::create('deployment', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->boolean('running')->default(true);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('deployment');
    }
}
