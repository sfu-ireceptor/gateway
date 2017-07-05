<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLocalJobTable extends Migration {

	public function up()
	{
		Schema::create('local_job', function(Blueprint $table) {
			$table->increments('id');
			$table->text('queue');
			$table->text('description');
			$table->text('status');
			$table->dateTime('submitted');
			$table->dateTime('start');
			$table->dateTime('end');
			$table->timestamps();
    	});
    }

	public function down()
	{
		Schema::dropIfExists('local_job');
	}


}
