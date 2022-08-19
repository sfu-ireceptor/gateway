<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterRestServiceTableAddCountryField extends Migration
{
    public function up()
    {
        Schema::table('rest_service', function ($table) {
            $table->text('country')->nullable();
            $table->text('logo')->nullable();
        });
    }

    public function down()
    {
        Schema::table('rest_service', function ($table) {
            $table->dropColumn('country');
            $table->dropColumn('logo');
        });
    }
}
