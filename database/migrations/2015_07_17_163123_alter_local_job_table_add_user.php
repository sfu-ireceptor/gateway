<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterLocalJobTableAddUser extends Migration {

	public function up()
	{
		Schema::table('local_job', function($table)
		{
		    $table->text('user');
		});
	}

	public function down()
	{
		Schema::table('local_job', function($table)
		{
		    $table->dropColumn('user');
		});
	}

}
