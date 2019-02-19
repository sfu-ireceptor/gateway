<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call(RestServiceGroupSeeder::class);
        $this->call(RestServiceSeeder::class);
        $this->call(FieldNameSeeder::class);
    }
}
