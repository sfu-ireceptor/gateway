<?php

namespace App;

use Illuminate\Support\Facades\Log;
use Jenssegers\Mongodb\Eloquent\Model;

class Antigens extends Model
{
    protected $connection = 'mongodb'; // https://github.com/jenssegers/laravel-mongodb
    protected $collection = 'antigens';
    protected $guarded = [];


    public static function cache_antigens($username, $rest_service_id = null)
    {
        Log::debug('Antigen::cache_antigens: Caching antigen data');
        // Perform the distinct query on the field to get a list of antigens
        $response_list = RestService::distinct('sequence', [$rest_service_id], 'ir_antigen_ref');

        // Process each response. There is only one as we only asked for one service above,
        // but the distinct returns an array of responses.
        foreach ($response_list as $rest_service_id => $antigen_array) {
            foreach ($antigen_array as $antigen_id) {
                // The query will return null in the list if there are Rearrangements
                // with no antigens, so we ignore the null response
                if ($antigen_id != null) {

                    // Set up our data to store in the DB
                    $t = [];
                    $t['antigen_id'] = $antigen_id;

                    // Look to see if the antigen is already in the DB
                    $existing_antigen = Antigens::where('antigen_id', $antigen_id)->take(1)->get();

                    // If there is no record, create one, otherwise update the record.
                    if (count($existing_antigen) == 0) {
                        Log::debug('Creating antigen = ' . $antigen_id);
                        $t['antigen_name'] = Antigens::lookup_antigen_name($antigen_id);
                        Antigens::create($t);
                    } else {
                        // Warn if there is more than one record for this antigen.
                        if (count($existing_antigen) > 1) {
                            Log::warning('More than one antigen for ' . $antigen_id);
                        }
                        // If the names don't match, update the record. We only update the first record
                        // Log a warning, as a changing name is suspicious.
                        /*
                        if ($existing_antigen[0]['antigen_name'] != $t['antigen_name']) {
                            Log::debug('Updating antigen = ' . $antigen_id);
                            Log::warning('Antigen name for ' . $antigen_id . ' changing: ' . $existing_antigen[0]['antigen_name'] . ' => ' . $t['antigen_name']);
                            Antigens::where('_id', $existing_antigen[0]->_id)->update($t);
                        }
                         */
                    }
                }
            }
        }
    }

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
                $response = $client->get($antigen_array[1] . '.json');
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
