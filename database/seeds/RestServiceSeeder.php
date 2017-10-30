<?php

use Illuminate\Database\Seeder;
use App\RestService;

class RestServiceSeeder extends Seeder
{
public function run()
{
$l = [
[
'url' => 'https://ipa.ireceptor.org/',
'name' => 'iReceptor Public Archive',
'username' => '',
'password' => '',
'version' => 2,
],
[
'url' => 'https://vdj-dev.tacc.utexas.edu/ireceptor/',
'name' => 'VDJServer',
'username' => '',
'password' => '',
'version' => 2,
],
[
'url' => 'https://206.12.99.171/',
'name' => 'iReceptor Public Archive 2 (MongoDB)',
'username' => '',
'password' => '',
'version' => 2,
],
];

foreach ($l as $t) {
RestService::firstOrCreate(['url' => $t['url']], $t);
}
}
}
