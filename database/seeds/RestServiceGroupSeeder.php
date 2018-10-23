<?php

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
            ];

        foreach ($l as $t) {
            RestServiceGroup::firstOrCreate(['code' => $t['code']], $t);
        }
    }
}
