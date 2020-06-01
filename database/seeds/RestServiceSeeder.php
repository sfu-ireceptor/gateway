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
            ],
            [
                'url' => 'https://airr-api.ireceptor.org/airr/v1/',
                'name' => 'IPA ADC Test (small)',
            ],
            [
                'url' => 'https://airr-api2.ireceptor.org/airr/v1/',
                'name' => 'IPA ADC Test 2 (big)',
            ],
            [
                'url' => 'http://192.168.87.62/airr/v1/',
                'name' => 'IPA ADC Staging 2',
            ],
            [
                'url' => 'http://adc-turnkey.ireceptor.org/airr/v1/',
                'name' => 'ADC Turnkey',
            ],
            [
                'url' => 'http://turnkey-test2.ireceptor.org/airr/v1/',
                'name' => 'ADC Turnkey 2',
            ],
            [
                'url' => 'http://i3.ireceptor.org/airr/v1/',
                'name' => 'i3 (Sorbonne) local',
            ],
            [
                'url' => 'https://ipa1-staging.ireceptor.org/airr/v1/',
                'name' => 'Staging IPA 1 ADC',
            ],
            [
                'url' => 'https://ipa2-staging.ireceptor.org/airr/v1/',
                'name' => 'Staging IPA 2 ADC',
            ],
            [
                'url' => 'https://ipa3-staging.ireceptor.org/airr/v1/',
                'name' => 'Staging IPA 3 ADC',
            ],
            [
                'url' => 'https://ipa4-staging.ireceptor.org/airr/v1/',
                'name' => 'Staging IPA 4 ADC',
            ],
            [
                'url' => 'http://206.12.89.162/airr/v1/',
                'name' => 'IPA5 ADC',
            ],
            [
                'url' => 'http://airr-gold.ireceptor.org/airr/v1/',
                'name' => 'AIRR Gold',
            ],
            [
                'url' => 'http://covid19.ireceptor.org/airr/v1/',
                'name' => 'COVID 19',
            ],
            [
                'url' => 'http://covid19-1.ireceptor.org/airr/v1/',
                'name' => 'COVID 19-1',
            ],
            [
                'url' => 'http://covid19-2.ireceptor.org/airr/v1/',
                'name' => 'COVID 19-2',
            ],
            [
                'url' => 'http://covid19-3.ireceptor.org/airr/v1/',
                'name' => 'COVID 19-3',
            ],
        ];

        foreach ($l as $t) {
            RestService::firstOrCreate(['url' => $t['url']], $t);
        }
    }
}
