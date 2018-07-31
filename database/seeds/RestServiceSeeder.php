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
                    'url' => 'https://ipa2.ireceptor.org/',
                    'name' => 'iReceptor Public Archive (MongoDB)',
                    'username' => '',
                    'password' => '',
                    'version' => 2,
                ],
                [
                    'url' => 'https://ipa3.ireceptor.org/',
                    'name' => 'iReceptor Public Archive (MongoDB)',
                    'username' => '',
                    'password' => '',
                    'version' => 2,
                ],
                [
                    'url' => 'http://206.12.99.176:8080/',
                    'name' => 'iReceptor Public Archive (Turnkey)',
                    'username' => '',
                    'password' => '',
                    'version' => 2,
                ],
                [
                    'url' => 'https://ipa-mono.ireceptor.org/',
                    'name' => 'iReceptor Public Archive (Mono)',
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
