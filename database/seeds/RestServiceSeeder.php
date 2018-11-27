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
                    'url' => 'https://ipa-sql.ireceptor.org/',
                    'name' => 'iReceptor Public Archive (SQL)',
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
                    'rest_service_group_code' => 'ipa',
                    'url' => 'https://ipa-staging.ireceptor.org/',
                    'name' => 'iReceptor Public Archive [staging]',
                    'username' => '',
                    'password' => '',
                    'version' => 2,
                ],
                [
                    'rest_service_group_code' => 'ipa',
                    'url' => 'https://ipa.ireceptor.org/',
                    'name' => 'iReceptor Public Archive',
                    'username' => '',
                    'password' => '',
                    'version' => 2,
                ],
                [
                    'rest_service_group_code' => 'ipa',
                    'url' => 'http://206.12.99.176:8080/',
                    'name' => 'iReceptor Public Archive (Turnkey)',
                    'username' => '',
                    'password' => '',
                    'version' => 2,
                ],
                [
                    'rest_service_group_code' => 'ipa',
                    'url' => 'https://ipa-mono.ireceptor.org/',
                    'name' => 'iReceptor Public Archive (Mono)',
                    'username' => '',
                    'password' => '',
                    'version' => 2,
                ],
                [
                    'rest_service_group_code' => 'ipa',
                    'url' => 'http://192.168.108.240:8080/',
                    'name' => 'iReceptor Turnkey 2 (tk2)',
                    'username' => '',
                    'password' => '',
                    'version' => 2,
                ],
                [
                    'rest_service_group_code' => '',
                    'url' => 'https://206.12.88.221/',
                    'name' => 'IPA Arbutus 1',
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
