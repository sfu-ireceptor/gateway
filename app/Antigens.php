<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;
use Illuminate\Support\Facades\Log;


class Antigens extends Model
{
    protected $connection = 'mongodb'; // https://github.com/jenssegers/laravel-mongodb
    protected $collection = 'antigens';
    protected $guarded = [];

    public static function lookup_antigen_name($antigen_id)
    {
        $antigen_name = '';
        try {
            $defaults = [];
            $defaults['verify'] = false;    // accept self-signed SSL certificates

            $antigen_array = explode(':', $antigen_id);
            if (count($antigen_array) == 2) {
                $defaults['base_uri'] = 'https://rest.uniprot.org/uniprotkb/';
                $client = new \GuzzleHttp\Client($defaults);
                $response = $client->get($antigen_array[1].'.json');
                $body = $response->getBody();
                $t = json_decode($body);
                // TODO: Check that these keys exist for object $t
                $antigen_name = $t->proteinDescription->recommendedName->fullName->value;
            }
        } catch (\Exception $e) {
            $error_message = $e->getMessage();
            Log::error('Antigens: UNIProt request failed: ' . $error_message);
            $antigen_name = '';
        }

        return $antigen_name;
    }

}
