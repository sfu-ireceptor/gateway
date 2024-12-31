<?php

namespace Database\Seeders;

use App\RestServiceGroup;
use Illuminate\Database\Seeder;

class RestServiceGroupSeeder extends Seeder
{
    public function run()
    {
        $l = [
            [
                'code' => 'ipa',
                'name' => 'iReceptor Public Archive',
            ],
            [
                'code' => 'c19',
                'name' => 'AIRR COVID-19',
            ],
            [
                'code' => 't1d',
                'name' => 'T1D',
            ],
            [
                'code' => 'greiff',
                'name' => 'Greiff Lab',
            ],
        ];

        foreach ($l as $t) {
            echo 'Adding group ' . $t['name'] . "\n";
            RestServiceGroup::firstOrCreate(['code' => $t['code']], $t);
        }
    }
}
