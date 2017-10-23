<?php

use Illuminate\Database\Seeder;
use App\SampleField;

class SampleFieldSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    	DB::table('sample_field')->truncate();

		$l = [
            [
              'key' => 'a',
              'airr' => 'b',
              'airr_full' => 'c',
              'ir_v1' => 'd',
              'ir_v2' => 'e',
              'ir_full' => 'f',
              'ir_short' => 'g',
            ],
        ];

        foreach ($l as $item) {
	        SampleField::create($item);
	    }
    }
}
