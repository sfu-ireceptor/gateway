<?php

use App\RestService;
use Illuminate\Database\Seeder;

class RestServiceSeeder extends Seeder
{
    public function run()
    {
        $l = [
            [
                'url' => 'https://ipa1.ireceptor.org/airr/v1/',
                'name' => 'IPA 1',
                'rest_service_group_code' => 'ipa',
            ],
            [
                'url' => 'https://ipa2.ireceptor.org/airr/v1/',
                'name' => 'IPA 2',
                'rest_service_group_code' => 'ipa',
            ],
            [
                'url' => 'https://ipa3.ireceptor.org/airr/v1/',
                'name' => 'IPA 3',
                'rest_service_group_code' => 'ipa',
            ],
            [
                'url' => 'https://ipa4.ireceptor.org/airr/v1/',
                'name' => 'IPA 4',
                'rest_service_group_code' => 'ipa',
            ],
            [
                'url' => 'http://ipa5.ireceptor.org/airr/v1/',
                'name' => 'IPA 5',
                'rest_service_group_code' => 'ipa',
            ],
            [
                'url' => 'https://ipa1-staging.ireceptor.org/airr/v1/',
                'name' => 'IPA 1 Staging',
            ],
            [
                'url' => 'https://ipa2-staging.ireceptor.org/airr/v1/',
                'name' => 'IPA 2 Staging',
            ],
            [
                'url' => 'https://ipa3-staging.ireceptor.org/airr/v1/',
                'name' => 'IPA 3 Staging',
            ],
            [
                'url' => 'https://ipa4-staging.ireceptor.org/airr/v1/',
                'name' => 'IPA 4 Staging',
            ],
            [
                'url' => 'http://206.12.89.162/airr/v1/',
                'name' => 'IPA 5 Staging',
            ],
            [
                'url' => 'http://covid19-1.ireceptor.org/airr/v1/',
                'name' => 'COVID 19-1',
                'rest_service_group_code' => 'c19',
            ],
            [
                'url' => 'http://covid19-1-staging.ireceptor.org/airr/v1/',
                'name' => 'COVID 19-1 Staging',
            ],
            [
                'url' => 'http://covid19-2.ireceptor.org/airr/v1/',
                'name' => 'COVID 19-2',
                'rest_service_group_code' => 'c19',
            ],
            [
                'url' => 'http://covid19-2-staging.ireceptor.org/airr/v1/',
                'name' => 'COVID 19-2 Staging',
            ],
            [
                'url' => 'http://covid19-3.ireceptor.org/airr/v1/',
                'name' => 'COVID 19-3',
                'rest_service_group_code' => 'c19',
            ],
            [
                'url' => 'http://covid19-3-staging.ireceptor.org/airr/v1/',
                'name' => 'COVID 19-3 Staging',
            ],
            [
                'url' => 'http://covid19-4.ireceptor.org/airr/v1/',
                'name' => 'COVID 19-4',
                'rest_service_group_code' => 'c19',
            ],
            [
                'url' => 'http://covid19-4-staging.ireceptor.org/airr/v1/',
                'name' => 'COVID 19-4 Staging',
            ],
            [
                'url' => 'https://vdj-staging.tacc.utexas.edu/airr/v1/',
                'name' => 'VDJServer Staging',
            ],
            [
                'url' => 'https://vdjserver.org/airr/v1/',
                'name' => 'VDJServer',
            ],
            [
                'url' => 'http://irec.i3lab.fr/airr/v1/',
                'name' => 'i3 AIRR',
            ],
            [
                'url' => 'https://pangaea.scireptor.net/airr/v1/',
                'name' => 'sciReptor',
            ],
            [
                'url' => 'https://ireceptor-us.medgenome.com/airr/v1/',
                'name' => 'MedGenome',
            ],
            [
                'url' => 'http://gur-irplus.ireceptor.org/airr/v1/',
                'name' => 'Gur IR+',
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
