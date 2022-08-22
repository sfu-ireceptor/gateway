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
                'country' => 'Canada',
                'logo' => 'ireceptor.png',
                'rest_service_group_code' => 'ipa',
            ],
            [
                'url' => 'https://ipa2.ireceptor.org/airr/v1/',
                'name' => 'IPA 2',
                'country' => 'Canada',
                'logo' => 'ireceptor.png',
                'rest_service_group_code' => 'ipa',
            ],
            [
                'url' => 'https://ipa3.ireceptor.org/airr/v1/',
                'name' => 'IPA 3',
                'country' => 'Canada',
                'logo' => 'ireceptor.png',
                'rest_service_group_code' => 'ipa',
            ],
            [
                'url' => 'https://ipa4.ireceptor.org/airr/v1/',
                'name' => 'IPA 4',
                'country' => 'Canada',
                'logo' => 'ireceptor.png',
                'rest_service_group_code' => 'ipa',
            ],
            [
                'url' => 'http://ipa5.ireceptor.org/airr/v1/',
                'name' => 'IPA 5',
                'country' => 'Canada',
                'logo' => 'ireceptor.png',
                'rest_service_group_code' => 'ipa',
            ],
            [
                'url' => 'https://ipa1-staging.ireceptor.org/airr/v1/',
                'name' => 'IPA 1 Staging',
                'country' => 'Canada',
                'logo' => 'ireceptor.png',
            ],
            [
                'url' => 'https://ipa2-staging.ireceptor.org/airr/v1/',
                'name' => 'IPA 2 Staging',
                'country' => 'Canada',
                'logo' => 'ireceptor.png',
            ],
            [
                'url' => 'https://ipa3-staging.ireceptor.org/airr/v1/',
                'name' => 'IPA 3 Staging',
                'country' => 'Canada',
                'logo' => 'ireceptor.png',
            ],
            [
                'url' => 'https://ipa4-staging.ireceptor.org/airr/v1/',
                'name' => 'IPA 4 Staging',
                'country' => 'Canada',
                'logo' => 'ireceptor.png',
            ],
            [
                'url' => 'http://ipa5-staging.ireceptor.org/airr/v1/',
                'name' => 'IPA 5 Staging',
                'rest_service_group_code' => 'ipa',
                'country' => 'Canada',
                'logo' => 'ireceptor.png',
            ],
            [
                'url' => 'http://covid19-1.ireceptor.org/airr/v1/',
                'name' => 'COVID 19-1',
                'country' => 'Canada',
                'logo' => 'ireceptor.png',
                'rest_service_group_code' => 'c19',
            ],
            [
                'url' => 'http://covid19-1-staging.ireceptor.org/airr/v1/',
                'name' => 'COVID 19-1 Staging',
                'country' => 'Canada',
                'logo' => 'ireceptor.png',
            ],
            [
                'url' => 'http://covid19-2.ireceptor.org/airr/v1/',
                'name' => 'COVID 19-2',
                'country' => 'Canada',
                'logo' => 'ireceptor.png',
                'rest_service_group_code' => 'c19',
            ],
            [
                'url' => 'http://covid19-2-staging.ireceptor.org/airr/v1/',
                'name' => 'COVID 19-2 Staging',
                'country' => 'Canada',
                'logo' => 'ireceptor.png',
            ],
            [
                'url' => 'http://covid19-3.ireceptor.org/airr/v1/',
                'name' => 'COVID 19-3',
                'country' => 'Canada',
                'logo' => 'ireceptor.png',
                'rest_service_group_code' => 'c19',
            ],
            [
                'url' => 'http://covid19-3-staging.ireceptor.org/airr/v1/',
                'name' => 'COVID 19-3 Staging',
                'country' => 'Canada',
                'logo' => 'ireceptor.png',
            ],
            [
                'url' => 'http://covid19-4.ireceptor.org/airr/v1/',
                'name' => 'COVID 19-4',
                'country' => 'Canada',
                'logo' => 'ireceptor.png',
                'rest_service_group_code' => 'c19',
            ],
            [
                'url' => 'http://covid19-4-staging.ireceptor.org/airr/v1/',
                'name' => 'COVID 19-4 Staging',
                'country' => 'Canada',
                'logo' => 'ireceptor.png',
            ],
            [
                'url' => 'https://vdj-staging.tacc.utexas.edu/airr/v1/',
                'name' => 'VDJServer Staging',
            ],
            [
                'url' => 'https://vdjserver.org/airr/v1/',
                'name' => 'VDJServer',
                'country' => 'United States',
                'logo' => 'vdjserver.png',
            ],
            [
                'url' => 'http://irec.i3lab.fr/airr/v1/',
                'name' => 'i3 AIRR',
                'country' => 'France',
                'logo' => '',
            ],
            [
                'url' => 'https://stats-staging.ireceptor.org/airr/v1/',
                'name' => 'Stats Demo',
            ],
            [
                'url' => 'https://scireptor.dkfz.de/airr/v1/',
                'name' => 'sciReptor',
                'country' => 'Germany',
                'logo' => 'dkfz.png',
            ],
            [
                'url' => 'https://ireceptor-us.medgenome.com/airr/v1/',
                'name' => 'MedGenome',
            ],
            [
                'url' => 'http://airr-seq.vdjbase.org/airr/v1/',
                'name' => 'VDJbase',
                'country' => 'Israel',
                'logo' => 'vdjbase.png',
            ],
            [
                'url' => 'http://secure.ireceptor.org/middleware/airr/v1/',
                'name' => 'Secure IPA1',
                'country' => 'Canada',
                'logo' => 'ireceptor.png',            ],
            [
                'url' => 'http://turnkey-test2.ireceptor.org/airr/v2/',
                'name' => 'ADC Turnkey 2 V2',
                'country' => 'Canada',
                'logo' => 'ireceptor.png',
            ],
            [
                'url' => 'http://154.127.124.38:2222/airr/v1/',
                'name' => 'NICD - South Africa',
                'country' => 'South Africa',
                'logo' => 'nicd.jpg',
            ],
            [
                'url' => 'http://single-cell.ireceptor.org/airr/v1/',
                'name' => 'Single Cell Repo',
                'country' => 'Canada',
                'logo' => 'ireceptor.png',
            ],
            [
                'url' => 'https://agschwab.uni-muenster.de/airr/v1/',
                'name' => 'University of Münster',
                'country' => 'Germany',
                'logo' => 'munster.jpg',
            ],
            [
                'url' => 'https://vdjbaseirplus.biu.ac.il/airr/v1/',
                'name' => 'Bar-Ilan University',
            ],
            [
                'url' => 'https://roche-airr.ireceptor.org/airr/v1/',
                'name' => 'Roche',
                'country' => 'Canada',
                'logo' => 'roche.png',
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
