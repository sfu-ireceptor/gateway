<?php

use App\RestService;
use Illuminate\Database\Seeder;

class RestServicePrivateSeeder extends Seeder
{

    public function __construct()
    {
        $this->table = 'rest_service';
        $this->filename = config('ireceptor.seeders_data_folder') . '/private_rest_services.tsv';
        $this->offset_rows = 1;
        $this->csv_delimiter = "\t";
    }	

    public function run()
    {
    	$line = 0;
		$handle = fopen($this->filename, 'r');
		while (($row = fgetcsv($handle, 1024, "\t")) !== FALSE) {
			$line++;

			// skip first line
			if($line == 1) {
				continue;
			}
			$t = [];
			$t['url'] = $row[0];
			$t['name'] = $row[1];

			RestService::updateOrCreate(['url' => $t['url']], $t);
		}
	}
}
