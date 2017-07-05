<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddJobFields extends Migration {

	public function up()
	{
		Schema::table('job', function($table)
		{
		    $table->integer('status')->unsigned();
		    $table->integer('progress')->unsigned();
		});
	}

	public function down()
	{
		Schema::table('job', function($table)
		{
			$table->dropColumn('status');
			$table->dropColumn('progress');
		});
	}

}
