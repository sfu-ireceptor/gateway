<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AlterQueryTableChangeParams extends Migration
{
    public function up()
    {
        Schema::table('query', function ($table) {
            $table->mediumText('params')->comment(' ')->change();
        });
    }

    public function down()
    {
        Schema::table('query', function ($table) {
            $table->text('params')->comment('')->change();
        });
    }
}
