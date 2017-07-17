<?php

use Illuminate\Database\Migrations\Migration;

class AddAppToJob extends Migration
{
    public function up()
    {
        Schema::table('job', function ($table) {
            $table->text('app');
        });
    }

    public function down()
    {
        Schema::table('job', function ($table) {
            $table->dropColumn('app');
        });
    }
}
