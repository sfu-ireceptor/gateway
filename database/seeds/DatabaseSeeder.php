<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call(RestServiceSeeder::class);
        $this->call(SequenceColumnNameSeeder::class);
        $this->call(FieldNameSeeder::class);
    }
}
