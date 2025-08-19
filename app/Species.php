<?php

namespace App;

use Illuminate\Support\Facades\Log;
use Jenssegers\Mongodb\Eloquent\Model;

class Species extends Model
{
    protected $connection = 'mongodb'; // https://github.com/jenssegers/laravel-mongodb
    protected $collection = 'species';
    protected $guarded = [];

    public static function cache_species($username, $rest_service_id = null)
    {
        Log::debug('Species::cache_species: Caching species data');
        // Perform the distinct query on the field to get a list of species
        $response_list = RestService::distinct('sequence', [$rest_service_id], 'ir_species_ref');

        // Process each response. There is only one as we only asked for one service above,
        // but the distinct returns an array of responses.
        foreach ($response_list as $rest_service_id => $species_array) {
            foreach ($species_array as $species_id) {
                // The query will return null in the list if there are Rearrangements
                // with no species, so we ignore the null response
                if ($species_id != null) {
                    // Set up our data to store in the DB
                    $t = [];
                    $t['species_id'] = $species_id;

                    // Look to see if the species is already in the DB
                    $existing_species = Species::where('species_id', $species_id)->take(1)->get();

                    // If there is no record, create one, otherwise update the record.
                    if (count($existing_species) == 0) {
                        Log::debug('Creating species = ' . $species_id);
                        $t['species_name'] = Species::lookup_species_name($species_id);
                        Species::create($t);
                    } else {
                        // Warn if there is more than one record for this species.
                        if (count($existing_species) > 1) {
                            Log::warning('More than one species for ' . $species_id);
                        }
                        // If the names don't match, update the record. We only update the first record
                        // Log a warning, as a changing name is suspicious.
                        /*
                        if ($existing_species[0]['species_name'] != $t['species_name']) {
                            Log::debug('Updating species = ' . $species_id);
                            Log::warning('Species name for ' . $species_id . ' changing: ' . $existing_species[0]['species_name'] . ' => ' . $t['species_name']);
                            Species::where('_id', $existing_species[0]->_id)->update($t);
                        }
                         */
                    }
                }
            }
        }
    }

    public static function lookup_species_name($species_id)
    {
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
