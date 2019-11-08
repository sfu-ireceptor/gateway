<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

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
