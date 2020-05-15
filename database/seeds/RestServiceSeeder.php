<?php

use App\RestService;
use Illuminate\Database\Seeder;

class RestServiceSeeder extends Seeder
{
    public function run()
    {
        $l = [
            [
                'url' => 'https://vdjserver.org/airr/v1/',
                'name' => 'VDJServer ADC',
                'username' => '',
                'password' => '',
                'version' => 2,
            ],
            [
                'url' => 'https://airr-api.ireceptor.org/airr/v1/',
                'name' => 'IPA ADC Test (small)',
                'username' => '',
                'password' => '',
                'version' => 2,
            ],
            [
                'url' => 'https://airr-api2.ireceptor.org/airr/v1/',
                'name' => 'IPA ADC Test 2 (big)',
                'username' => '',
                'password' => '',
                'version' => 2,
            ],
            [
                'url' => 'http://192.168.87.62/airr/v1/',
                'name' => 'IPA ADC Staging 2',
                'username' => '',
                'password' => '',
                'version' => 2,
            ],
            [
                'url' => 'http://adc-turnkey.ireceptor.org/airr/v1/',
                'name' => 'ADC Turnkey',
                'username' => '',
                'password' => '',
                'version' => 2,
            ],
            [
                'url' => 'http://turnkey-test2.ireceptor.org/airr/v1/',
                'name' => 'ADC Turnkey 2',
                'username' => '',
                'password' => '',
                'version' => 2,
            ],
            [
                'url' => 'http://i3.ireceptor.org/airr/v1/',
                'name' => 'i3 (Sorbonne) local',
                'username' => '',
                'password' => '',
                'version' => 2,
            ],
            [
                'url' => 'https://ipa1-staging.ireceptor.org/airr/v1/',
                'name' => 'Staging IPA 1 ADC',
                'username' => '',
                'password' => '',
            ],
            [
                'url' => 'https://ipa2-staging.ireceptor.org/airr/v1/',
                'name' => 'Staging IPA 2 ADC',
                'username' => '',
                'password' => '',
            ],
            [
                'url' => 'https://ipa3-staging.ireceptor.org/airr/v1/',
                'name' => 'Staging IPA 3 ADC',
                'username' => '',
                'password' => '',
            ],
            [
                'url' => 'https://ipa4-staging.ireceptor.org/airr/v1/',
                'name' => 'Staging IPA 4 ADC',
                'username' => '',
                'password' => '',
            ],
            [
                'url' => 'http://206.12.89.162/airr/v1/',
                'name' => 'IPA5 ADC',
                'username' => '',
                'password' => '',
            ],
            [
                'url' => 'http://airr-gold.ireceptor.org/airr/v1/',
                'name' => 'AIRR Gold',
                'username' => '',
                'password' => '',
            ],
            [
                'url' => 'http://covid19.ireceptor.org/airr/v1/',
                'name' => 'COVID 19',
                'username' => '',
                'password' => '',
            ],
            [
                'url' => 'http://covid19-1.ireceptor.org/airr/v1/',
                'name' => 'COVID 19-1',
                'username' => '',
                'password' => '',
            ],

        ];

        foreach ($l as $t) {
            RestService::firstOrCreate(['url' => $t['url']], $t);
        }
    }
}
