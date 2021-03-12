<?php

use App\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run()
    {
        $l = [
            [
                'username' => 'mercury',
                'password' => 'tN4ebeaeETVPf6gg',
                'first_name' => 'Mercury',
                'last_name' => 'Planet',
                'email' => 'mercury@solarsystem.uni',
                'admin' => true,
            ],
        ];

        foreach ($l as $t) {
            User::firstOrCreate(['username' => $t['username']], $t);
        }
    }
}
