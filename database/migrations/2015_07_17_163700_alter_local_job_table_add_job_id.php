<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterLocalJobTableAddJobId extends Migration {

	public function up()
	{
		Schema::table('local_job', function($table)
		{
		    $table->integer('job_id')->unsigned();
		});
	}

	public function down()
	{
		Schema::table('local_job', function($table)
		{
		    $table->dropColumn('job_id');
		});
	}

}
