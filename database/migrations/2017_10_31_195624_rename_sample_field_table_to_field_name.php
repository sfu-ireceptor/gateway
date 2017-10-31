<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

class RenameSampleFieldTableToFieldName extends Migration
{
    public function up()
    {
        Schema::rename('sample_field', 'field_name');
    }

    public function down()
    {
        Schema::rename('field_name', 'sample_field');
    }
}
