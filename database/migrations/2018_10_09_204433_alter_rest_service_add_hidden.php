<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
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
        $table->dropColumn('hidden');
    }
}
