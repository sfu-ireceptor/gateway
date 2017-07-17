<?php

namespace App\Http\Controllers;

class TestController extends Controller
{
    public function getIndex()
    {
        echo 'index';
        $tables = \DB::connection()->getDoctrineSchemaManager()->listTableNames();
        foreach ($tables as $table) {
            echo $table."\n";
            // code...
        }
    }
}
