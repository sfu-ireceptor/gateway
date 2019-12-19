<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterAllTablesTimestampsForLaravel5 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // minor fix for entries created manually in some tables
        $affected = DB::update('update rest_service set created_at = ? where created_at = ?', ['2017-06-20 03:52:50', '0000-00-00 00:00:00']);

        $tables = \DB::connection()->getDoctrineSchemaManager()->listTableNames();
        foreach ($tables as $table_name) {
            if ($table_name != 'migrations' && $table_name != 'password_resets') {
                Schema::table($table_name, function (Blueprint $table) {
                    $table->dateTime('created_at')->nullable()->default(null)->change();
                    $table->dateTime('updated_at')->nullable()->default(null)->change();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
