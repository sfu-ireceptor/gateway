<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRenewTokenColumns extends Migration {

	public function up()
	{
		Schema::table('user', function($table)
		{
		    $table->string('refresh_token')->nullable();
		    $table->dateTime('token_expiration_date')->nullable();
		});
	}

	public function down()
	{
		Schema::table('user', function($table)
		{
			$table->dropColumn('refresh_token');
			$table->dropColumn('token_expiration_date');
		});
	}
}
