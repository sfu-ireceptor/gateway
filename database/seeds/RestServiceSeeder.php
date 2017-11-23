<?php

use App\RestService;
use Illuminate\Database\Seeder;

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
                    'version' => 2,
                ],
                [
                    'url' => 'https://vdj-staging.tacc.utexas.edu/ireceptor/',
                    'name' => 'VDJServer',
                    'username' => '',
                    'password' => '',
                    'version' => 2,
                ],
                [
                    'url' => 'https://206.12.99.171/',
                    'name' => 'MongoDB iReceptor Public Archive',
                    'username' => '',
                    'password' => '',
                    'version' => 2,
                ],
                [
                    'url' => 'http://206.12.99.227:8080/',
                    'name' => 'Turnkey iReceptor Public Archive',
                    'username' => '',
                    'password' => '',
                    'version' => 2,
                ],
            ];

        foreach ($l as $t) {
            RestService::firstOrCreate(['url' => $t['url']], $t);
        }
    }
}
