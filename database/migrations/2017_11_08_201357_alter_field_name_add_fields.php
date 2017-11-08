<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterFieldNameAddFields extends Migration
{
    public function up()
    {
        Schema::table('field_name', function ($table) {
            $table->text('airr_description')->nullable();
            $table->text('airr_example')->nullable();
        });
    }

    public function down()
    {
        Schema::table('field_name', function ($table) {
            $table->dropColumn('airr_description');
            $table->dropColumn('airr_example');
        });
    }
}
