<?php

use Illuminate\Database\Seeder;
use App\RestService;

class RestServiceSeeder extends Seeder
{
    public function run()
    {
        $l = [
            [
              'url' => 'https://ipa.ireceptor.org/',
              'name' => 'iReceptor Public Archive',
              'username' => '',
              'password' => '',
            ],
            [
              'url' => 'https://vdj-dev.tacc.utexas.edu/ireceptor/',
              'name' => 'VDJServer',
              'username' => '',
              'password' => '',
            ],
        ];

        foreach ($l as $t) {
          RestService::firstOrCreate(['url' => $t['url']], ['name' => $t['name']]);
        }
    }
}
