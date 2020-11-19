<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterUserTableAddGalaxyFields extends Migration
{
    public function up()
    {
        Schema::table('user', function ($table) {
            $table->text('galaxy_url')->nullable();
            $table->text('galaxy_tool_id')->nullable();            
        });
    }

    public function down()
    {
        Schema::table('user', function ($table) {
            $table->dropColumn('galaxy_url');
            $table->dropColumn('galaxy_tool_id');
        });
    }
}
