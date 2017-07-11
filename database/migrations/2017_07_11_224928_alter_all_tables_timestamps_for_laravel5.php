<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterAllTablesTimestampsForLaravel5 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tables = \DB::connection()->getDoctrineSchemaManager()->listTableNames();
        foreach ($tables as $table_name) {
            if($table_name != 'migrations' && $table_name != 'password_resets')
            {
                Schema::table($table_name, function (Blueprint $table) {
                    $table->dateTime('created_at')->nullable()->default(NULL)->change();
                    $table->dateTime('updated_at')->nullable()->default(NULL)->change();
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
