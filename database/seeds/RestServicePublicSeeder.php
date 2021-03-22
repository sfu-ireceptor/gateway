<?php

use Illuminate\Database\Seeder;
use App\RestService;

class RestServicePublicSeeder extends Seeder
{
public function run()
    {
        $l = [
			    [
			        'url' => 'https://ipa1.ireceptor.org/airr/v1/',
			        'name' => 'IPA 1',
			        'nb_sequences' => 239596233,
			        'rest_service_group_code' => 'ipa',
			    ],
			    [
			        'url' => 'https://ipa2.ireceptor.org/airr/v1/',
			        'name' => 'IPA 2',
			        'nb_sequences' => 312157920,
			        'rest_service_group_code' => 'ipa',
			    ],
			    [
			        'url' => 'https://ipa3.ireceptor.org/airr/v1/',
			        'name' => 'IPA 3',
			        'nb_sequences' => 452383378,
			        'rest_service_group_code' => 'ipa',
			    ],
			    [
			        'url' => 'https://ipa4.ireceptor.org/airr/v1/',
			        'name' => 'IPA 4',
			        'nb_sequences' => 388707880,
			        'rest_service_group_code' => 'ipa',
			    ],
			    [
			        'url' => 'http://ipa5.ireceptor.org/airr/v1/',
			        'name' => 'IPA 5',
			        'nb_sequences' => 465253902,
			        'rest_service_group_code' => 'ipa',
			    ],
			    [
			        'url' => 'http://covid19-1.ireceptor.org/airr/v1/',
			        'name' => 'COVID 19-1',
			        'nb_sequences' => 49373265,
			        'rest_service_group_code' => 'c19',
			    ],
			    [
			        'url' => 'http://covid19-2.ireceptor.org/airr/v1/',
			        'name' => 'COVID 19-2',
			        'nb_sequences' => 154106183,
			        'rest_service_group_code' => 'c19',
			    ],
			    [
			        'url' => 'http://covid19-3.ireceptor.org/airr/v1/',
			        'name' => 'COVID 19-3',
			        'nb_sequences' => 168683398,
			        'rest_service_group_code' => 'c19',
			    ],
			    [
			        'url' => 'http://covid19-4.ireceptor.org/airr/v1/',
			        'name' => 'COVID 19-4',
			        'nb_sequences' => 374743217,
			        'rest_service_group_code' => 'c19',
			    ],
			    [
			        'url' => 'https://vdjserver.org/airr/v1/',
			        'nb_sequences' => 1428100943,
			        'name' => 'VDJServer',
			    ],
			    [
			        'url' => 'http://airr-seq.vdjbase.org/airr/v1/',
			        'nb_sequences' => 212547,
			        'name' => 'VDJbase',
			    ],
			];

        // if grouping is disabled, remove group code
        if (! config('ireceptor.group_repositories')) {
            foreach ($l as $k => $v) {
                $l[$k]['rest_service_group_code'] = null;
            }
        }

        foreach ($l as $t) {
            RestService::updateOrCreate(['url' => $t['url']], $t);
        }
    }
}
