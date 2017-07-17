<?php

use Illuminate\Database\Migrations\Migration;

class AddUserIdToJob extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('job', function ($table) {
            $table->integer('user_id')->unsigned();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('job', function ($table) {
            $table->dropColumn('user_id');
        });
    }
}
