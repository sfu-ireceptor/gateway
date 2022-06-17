<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterFieldNameTableAddApiVersion extends Migration
{
    public function up()
    {
        Schema::table('field_name', function ($table) {
            $table->text('api_version')->nullable();
        });
    }

    public function down()
    {
        Schema::table('field_name', function ($table) {
            $table->dropColumn('api_version');
        });
    }
}
