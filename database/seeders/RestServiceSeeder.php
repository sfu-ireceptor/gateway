<?php

namespace Database\Seeders;

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
                'url' => 'https://ipa5.ireceptor.org/airr/v1/',
                'name' => 'IPA 5',
                'country' => 'Canada',
                'logo' => 'ireceptor.png',
                'rest_service_group_code' => 'ipa',
            ],
            [
                'url' => 'https://ipa6.ireceptor.org/airr/v1/',
                'name' => 'IPA 6',
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
                'url' => 'https://ipa5-staging.ireceptor.org/airr/v1/',
                'name' => 'IPA 5 Staging',
                'rest_service_group_code' => 'ipa',
                'country' => 'Canada',
                'logo' => 'ireceptor.png',
            ],
            [
                'url' => 'https://covid19-1.ireceptor.org/airr/v1/',
                'name' => 'COVID 19-1',
                'country' => 'Canada',
                'logo' => 'ireceptor.png',
                'rest_service_group_code' => 'c19',
            ],
            [
                'url' => 'https://covid19-1-staging.ireceptor.org/airr/v1/',
                'name' => 'COVID 19-1 Staging',
                'country' => 'Canada',
                'logo' => 'ireceptor.png',
            ],
            [
                'url' => 'https://covid19-2.ireceptor.org/airr/v1/',
                'name' => 'COVID 19-2',
                'country' => 'Canada',
                'logo' => 'ireceptor.png',
                'rest_service_group_code' => 'c19',
            ],
            [
                'url' => 'https://covid19-2-staging.ireceptor.org/airr/v1/',
                'name' => 'COVID 19-2 Staging',
                'country' => 'Canada',
                'logo' => 'ireceptor.png',
            ],
            [
                'url' => 'https://covid19-3.ireceptor.org/airr/v1/',
                'name' => 'COVID 19-3',
                'country' => 'Canada',
                'logo' => 'ireceptor.png',
                'rest_service_group_code' => 'c19',
            ],
            [
                'url' => 'https://covid19-3-staging.ireceptor.org/airr/v1/',
                'name' => 'COVID 19-3 Staging',
                'country' => 'Canada',
                'logo' => 'ireceptor.png',
            ],
            [
                'url' => 'https://covid19-4.ireceptor.org/airr/v1/',
                'name' => 'COVID 19-4',
                'country' => 'Canada',
                'logo' => 'ireceptor.png',
                'rest_service_group_code' => 'c19',
            ],
            [
                'url' => 'https://covid19-4-staging.ireceptor.org/airr/v1/',
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
                'url' => 'https://airr-seq.vdjbase.org/airr/v1/',
                'name' => 'VDJbase',
                'country' => 'Israel',
                'logo' => 'vdjbase.png',
            ],
            [
                'url' => 'http://secure.ireceptor.org/middleware/airr/v1/',
                'name' => 'Secure IPA1',
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
                'logo' => 'ireceptor.png',
            ],
            [
                'url' => 'https://t1d-1.ireceptor.org/airr/v1/',
                'name' => 'T1D',
                'country' => 'Canada',
                'logo' => 'ireceptor.png',
                'rest_service_group_code' => 't1d',
            ],
            [
                'url' => 'https://t1d-2.ireceptor.org/airr/v1/',
                'name' => 'T1D-2',
                'country' => 'Canada',
                'logo' => 'ireceptor.png',
                'rest_service_group_code' => 't1d',
            ],
            [
                'url' => 'https://t1d-3.ireceptor.org/airr/v1/',
                'name' => 'T1D-3',
                'country' => 'Canada',
                'logo' => 'ireceptor.png',
                'rest_service_group_code' => 't1d',
            ],
            [
                'url' => 'https://greifflab-1.ireceptor.org/airr/v1/',
                'name' => 'Greiff Lab 1',
                'country' => 'Canada',
                'logo' => 'ireceptor.png',
                'rest_service_group_code' => 'greiff',
            ],
            [
                'url' => 'https://repository-staging.ireceptor.org/airr/v1/',
                'name' => 'Staging Repository',
            ],
            [
                'url' => 'https://mitmynid.sytes.net:59443/airr/v1/',
                'name' => 'Mitmynid',
                'country' => 'Portugal',
            ],
            [
                'url' => 'https://ireceptor.clalit.co.il/airr/v1/',
                'name' => 'Clalit',
                'country' => 'Israel',
            ],
            [
                'url' => 'https://hpap.ireceptor.org/airr/v1/',
                'name' => 'HPAP',
                'country' => 'Canada',
            ],
            [
                'url' => 'https://turnkey-test2.ireceptor.org/airr/v1/',
                'name' => 'Turnkey Test 2',
                'country' => 'Canada',
            ],
            [
                'url' => 'https://greifflab-1.ireceptor.org/airr/v1/',
                'name' => 'Greiff Lab 1',
                'country' => 'Canada',
                'rest_service_group_code' => 'greiff',
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
