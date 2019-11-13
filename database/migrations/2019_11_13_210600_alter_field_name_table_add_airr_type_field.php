<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AlterFieldNameTableAddAirrTypeField extends Migration
{
    public function up()
    {
        Schema::table('field_name', function ($table) {
            $table->text('airr_type')->nullable();
            $table->text('ir_api_input_type')->nullable();
        });
    }

    public function down()
    {
        Schema::table('field_name', function ($table) {
            $table->dropColumn('airr_type');
            $table->dropColumn('ir_api_input_type');
        });
    }
}

