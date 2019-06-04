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
                    'rest_service_group_code' => 'ipa',
                    'url' => 'http://ipa5.ireceptor.org/',
                    'name' => 'IPA 5',
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
                [
                    'url' => 'http://sidhu.ireceptor.org/',
                    'name' => 'Sidhu',
                    'username' => '',
                    'password' => '',
                    'version' => 2,
                ],
                [
                    'url' => 'http://206.12.91.5/',
                    'name' => 'Genoa Scratch',
                    'username' => '',
                    'password' => '',
                    'version' => 2,
                ],
                [
                    'url' => 'http://206.12.88.104/',
                    'name' => 'Genoa Turnkey',
                    'username' => '',
                    'password' => '',
                    'version' => 2,
                ],
                [
                    'url' => 'http://142.150.76.132/',
                    'name' => 'Sidhu-UoT',
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
