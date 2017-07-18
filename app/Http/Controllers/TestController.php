<?php

namespace App\Http\Controllers;

class TestController extends Controller
{
    public function getIndex()
    {
        echo 'index';

        // echo json_decode(config('services.agave.system_deploy.auth'));
        // var_dump(json_decode(config('services.agave.system_deploy.auth'), true));

        // $c = config('services.agave.system_deploy.auth');
        // $c = rtrim($c, "'");
        // $c = ltrim($c, "'");
        // var_dump(json_decode($c, true));

        // $tables = \DB::connection()->getDoctrineSchemaManager()->listTableNames();
        // foreach ($tables as $table) {
        //     echo $table."\n";
        //     // code...
        // }
    }
}
