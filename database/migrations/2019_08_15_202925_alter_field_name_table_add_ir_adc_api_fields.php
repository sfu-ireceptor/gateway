<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

class AlterFieldNameTableAddIrAdcApiFields extends Migration
{
    public function up()
    {
        Schema::table('field_name', function ($table) {
            $table->text('ir_adc_api_query')->nullable();
            $table->text('ir_adc_api_response')->nullable();
        });
    }

    public function down()
    {
        Schema::table('field_name', function ($table) {
            $table->dropColumn('ir_adc_api_query');
            $table->dropColumn('ir_adc_api_response');
        });
    }
}
