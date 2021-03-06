<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AlterFieldNameTableAddIrClass extends Migration
{
    public function up()
    {
        Schema::table('field_name', function ($table) {
            $table->text('ir_class')->nullable();
            $table->text('ir_subclass')->nullable();
        });
    }

    public function down()
    {
        Schema::table('field_name', function ($table) {
            $table->dropColumn('ir_class');
            $table->dropColumn('ir_subclass');
        });
    }
}
