<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableToMakeFiedsNullableForLaravel5 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('job', function (Blueprint $table) {
            $table->string('agave_id')->nullable()->default(null)->change();
            $table->string('agave_status')->nullable()->default(null)->change();
            $table->string('input_folder')->nullable()->default(null)->change();
            $table->integer('user_id')->unsigned()->nullable()->default(null)->change();
            $table->string('url')->nullable()->default(null)->change();
            $table->text('app')->nullable()->default(null)->change();
            $table->integer('status')->unsigned()->nullable()->default(null)->change();
            $table->integer('progress')->unsigned()->nullable()->default(null)->change();
        });

        Schema::table('local_job', function (Blueprint $table) {
            $table->text('queue')->nullable()->default(null)->change();
            $table->text('description')->nullable()->default(null)->change();
            $table->text('status')->nullable()->default(null)->change();
            $table->dateTime('submitted')->nullable()->default(null)->change();
            $table->dateTime('start')->nullable()->default(null)->change();
            $table->dateTime('end')->nullable()->default(null)->change();
            $table->text('user')->nullable()->default(null)->change();
            $table->integer('job_id')->unsigned()->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
