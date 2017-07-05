<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class JobAddUrlColumn extends Migration {

	public function up()
	{
		Schema::table('job', function($table)
		{
		    $table->string('url');
		});
	}

	public function down()
	{
		Schema::table('job', function($table)
		{
			$table->dropColumn('url');
		});
	}

}
