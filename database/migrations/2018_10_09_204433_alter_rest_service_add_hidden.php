<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

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
