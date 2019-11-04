<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AlterFieldNameTableAddDefaultOrderVisibility extends Migration
{
    public function up()
    {
        Schema::table('field_name', function ($table) {
            $table->integer('default_order')->default(9999);
            $table->boolean('default_visible')->default(false);
        });
    }

    public function down()
    {
        Schema::table('field_name', function ($table) {
            $table->dropColumn('default_order');
            $table->dropColumn('default_visible');
        });
    }
}
