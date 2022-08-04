<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterRestServiceTableAddContactFields extends Migration
{
    public function up()
    {
        Schema::table('rest_service', function ($table) {
            $table->text('contact_url')->nullable();
            $table->text('contact_email')->nullable();
        });
    }

    public function down()
    {
        Schema::table('rest_service', function ($table) {
            $table->dropColumn('contact_url');
            $table->dropColumn('contact_email');
        });
    }
}
