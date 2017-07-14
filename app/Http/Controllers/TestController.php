<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use App\Agave;

class TestController extends Controller
{
    public function getIndex()
    {
		echo 'index';
		$tables = \DB::connection()->getDoctrineSchemaManager()->listTableNames();
		foreach ($tables as $table) {
			echo $table . "\n";
			# code...
		}
    }
	
}
