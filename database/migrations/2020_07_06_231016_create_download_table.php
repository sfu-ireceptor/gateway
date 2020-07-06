<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDownloadTable extends Migration
{
    public function up()
    {
        Schema::create('download', function (Blueprint $table) {
            $table->increments('id');

            $table->string('username');

            $table->string('status');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->bigInteger('duration')->nullable();

            $table->text('page_url');
            $table->integer('nb_sequences')->nullable();

            $table->text('file_url')->nullable();
            $table->text('file_url_expiration')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('download');
    }
}
