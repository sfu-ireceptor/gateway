<?php

use Illuminate\Database\Migrations\Migration;

class AlterUserTableAddAdmin extends Migration
{
    public function up()
    {
        Schema::table('user', function ($table) {
            $table->boolean('admin')->default(false);
        });
    }

    public function down()
    {
        Schema::table('user', function ($table) {
            $table->dropColumn('admin');
        });
    }
}
