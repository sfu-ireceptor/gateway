<?php

use Illuminate\Database\Migrations\Migration;

class AlterRestServiceTableSetVersionTo2 extends Migration
{
    public function up()
    {
        $affected = DB::update('update rest_service set version = ?', [2]);
    }

    public function down()
    {
    }
}
