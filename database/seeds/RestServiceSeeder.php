<?php

use Illuminate\Database\Seeder;

class RestServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    	$table = 'rest_service';
        DB::table($table)->truncate();

        $l = [
            [
              'url' => 'https://ipa.ireceptor.org/',
              'name' => 'iReceptor Public Archive',
              'username' => '',
              'password' => '',
            ],
        ];

        foreach ($l as $item) {
            DB::table($table)->insert($item);
        }
    }
}
