<?php

use Illuminate\Database\Migrations\Migration;

class AlterRestServiceTableAddEnabled extends Migration
{
    public function up()
    {
        Schema::table('rest_service', function ($table) {
            $table->boolean('enabled')->default(true);
        });
    }

    public function down()
    {
        Schema::table('rest_service', function ($table) {
            $table->dropColumn('enabled');
        });
    }
}
