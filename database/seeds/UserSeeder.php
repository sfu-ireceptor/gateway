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
            [
                'username' => 'venus',
                'password' => 'tN4ebeaeETVPf6gg',
                'first_name' => 'Venus',
                'last_name' => 'Planet',
                'email' => 'venus@solarsystem.uni',
                'admin' => true,
            ],
            [
                'username' => 'earth',
                'password' => 'tN4ebeaeETVPf6gg',
                'first_name' => 'Earth',
                'last_name' => 'Planet',
                'email' => 'earth@solarsystem.uni',
                'admin' => true,
            ],
            [
                'username' => 'mars',
                'password' => 'tN4ebeaeETVPf6gg',
                'first_name' => 'Mars',
                'last_name' => 'Planet',
                'email' => 'mars@solarsystem.uni',
                'admin' => true,
            ],
        ];

        foreach ($l as $t) {
            User::firstOrCreate(['username' => $t['username']], $t);
        }
    }
}
