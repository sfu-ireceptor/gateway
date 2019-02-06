<?php

use App\RestService;
use Illuminate\Database\Seeder;

class RestServiceSeeder extends Seeder
{
    public function run()
    {
        $l = [
                [
                    'rest_service_group_code' => 'ipa',
                    'url' => 'https://ipa1.ireceptor.org/',
                    'name' => 'IPA 1',
                    'username' => '',
                    'password' => '',
                    'version' => 2,
                ],
                [
                    'rest_service_group_code' => 'ipa',
                    'url' => 'https://ipa2.ireceptor.org/',
                    'name' => 'IPA 2',
                    'username' => '',
                    'password' => '',
                    'version' => 2,
                ],
                [
                    'rest_service_group_code' => 'ipa',
                    'url' => 'https://ipa3.ireceptor.org/',
                    'name' => 'IPA 3',
                    'username' => '',
                    'password' => '',
                    'version' => 2,
                ],
                [
                    'rest_service_group_code' => 'ipa',
                    'url' => 'https://ipa4.ireceptor.org/',
                    'name' => 'IPA 4',
                    'username' => '',
                    'password' => '',
                    'version' => 2,
                ],
                [
                    'url' => 'https://vdjserver.org/ireceptor/',
                    'name' => 'VDJServer',
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
