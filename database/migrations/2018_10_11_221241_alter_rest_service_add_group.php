<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AlterRestServiceAddGroup extends Migration
{
    public function up()
    {
        Schema::table('rest_service', function ($table) {
            $table->text('rest_service_group_code')->nullable();
        });
    }

    public function down()
    {
        Schema::table('rest_service', function ($table) {
            $table->dropColumn('rest_service_group_code');
        });
    }
}
