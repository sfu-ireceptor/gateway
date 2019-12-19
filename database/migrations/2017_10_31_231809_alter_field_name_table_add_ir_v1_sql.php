<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AlterFieldNameTableAddIrV1Sql extends Migration
{
    public function up()
    {
        Schema::table('field_name', function ($table) {
            $table->string('ir_v1_sql')->nullable();
        });
    }

    public function down()
    {
        Schema::table('field_name', function ($table) {
            $table->dropColumn('ir_v1_sql');
        });
    }
}
