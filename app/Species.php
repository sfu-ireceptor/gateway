<?php

namespace App;

use Illuminate\Support\Facades\Log;
use Jenssegers\Mongodb\Eloquent\Model;

class Species extends Model
{
    protected $connection = 'mongodb'; // https://github.com/jenssegers/laravel-mongodb
    protected $collection = 'species';
    protected $guarded = [];

    public static function lookup_species_name($species_id) {
        $species_name = '';
        try {
            $defaults = [];
            $defaults['base_uri'] = 'https://www.ebi.ac.uk/ols4/api/ontologies/ncbitaxon/';
            $defaults['verify'] = false;    // accept self-signed SSL certificates

            $species_array = explode(':', $species_id);
            if (count($species_array) == 2) {
                $client = new \GuzzleHttp\Client($defaults);
                $query = 'terms?iri=http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FNCBITaxon_' . $species_array[1];
                $response = $client->get($query);
                $body = $response->getBody();
                $t = json_decode($body);

                $terms = $t->_embedded->terms;
                if (count($terms) > 0) {
                    $species_name = $terms[0]->label;
                }
            }
        } catch (\Exception $e) {
            $error_message = $e->getMessage();
            Log::error('OLS4 NCBITaxon request failed: ' . $error_message);
            $species_name = '';
        }

        return $species_name;
    }

}
