<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AlterRestServiceAddHidden extends Migration
{
    public function up()
    {
        Schema::table('rest_service', function ($table) {
            $table->boolean('hidden')->default(false);
        });
    }

    public function down()
    {
        Schema::table('rest_service', function ($table) {
            $table->dropColumn('hidden');
        });
    }
}
