<?php

use App\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function __construct()
    {
        $this->table = 'user';
        $this->filename = config('ireceptor.seeders_data_folder') . '/users.tsv';
        $this->offset_rows = 1;
        $this->csv_delimiter = "\t";
    }

    public function run()
    {
        $line = 0;
        $handle = fopen($this->filename, 'r');
        while (($row = fgetcsv($handle, 1024, "\t")) !== false) {
            $line++;

            // skip first line
            if ($line == 1) {
                continue;
            }
            $t = [];
            $t['username'] = $row[0];
            $t['first_name'] = $row[1];
            $t['last_name'] = $row[2];
            $t['email'] = $row[3];
            $t['password'] = $row[4];
            $t['admin'] = $row[5];

            User::updateOrCreate(['username' => $t['username']], $t);
        }
    }
}
