<?php

namespace App;

use Illuminate\Support\Facades\Log;
use Jenssegers\Mongodb\Eloquent\Model;

class Epitopes extends Model
{
    protected $connection = 'mongodb'; // https://github.com/jenssegers/laravel-mongodb
    protected $collection = 'epitopes';
    protected $guarded = [];

    public static function cache_epitopes($username, $rest_service_id = null)
    {
        Log::debug('Epitopes::cache_epitopes: Caching epitope data');
        // Perform the distinct query on the field to get a list of epitopes
        $response_list = RestService::distinct('sequence', [$rest_service_id], 'ir_epitope_ref');

        // Process each response. There is only one as we only asked for one service above,
        // but the distinct returns an array of responses.
        foreach ($response_list as $rest_service_id => $epitope_array) {
            foreach ($epitope_array as $epitope_id) {
                // The query will return null in the list if there are Rearrangements
                // with no epitopes, so we ignore the null response
                if ($epitope_id != null) {
                    // Set up our data to store in the DB
                    $t = [];
                    $t['epitope_id'] = $epitope_id;

                    // Look to see if the epitope is already in the DB
                    $existing_epitope = Epitopes::where('epitope_id', $epitope_id)->take(1)->get();

                    // If there is no record, create one, otherwise update the record.
                    if (count($existing_epitope) == 0) {
                        Log::debug('Creating epitope = ' . $epitope_id);
                        $t['epitope_sequence'] = Epitopes::lookup_epitope_sequence($epitope_id);
                        Epitopes::create($t);
                    } else {
                        // Warn if there is more than one record for this epitope.
                        if (count($existing_epitope) > 1) {
                            Log::warning('More than one epitope for ' . $epitope_id);
                        }
                        // If the names don't match, update the record. We only update the first record
                        // Log a warning, as a changing name is suspicious.
                        /*
                        if ($existing_epitope[0]['epitope_sequence'] != $t['epitope_sequence']) {
                            Log::debug('Updating epitope = ' . $epitope_id);
                            Log::warning('Epitope sequence for ' . $epitope_id . ' changing: ' . $existing_epitope[0]['epitope_sequence'] . ' => ' . $t['epitope_sequence']);
                            Epitopes::where('_id', $existing_epitope[0]->_id)->update($t);
                        }
                         */
                    }
                }
            }
        }
    }

    public static function lookup_epitope_sequence($epitope_id)
    {
        $epitope_sequence = '';
        try {
            $defaults = [];
            $defaults['verify'] = false;    // accept self-signed SSL certificates

            $defaults['base_uri'] = 'https://query-api.iedb.org/';
            $client = new \GuzzleHttp\Client($defaults);
            $response = $client->get('epitope_search?structure_iri=eq.' . $epitope_id);
            $body = $response->getBody();
            $t = json_decode($body);
            if (count($t) != 1) {
                Log::warn('Epitope - Could not find exactly one sequence for eptipope ID ' . $epitope_id);
            }
            // TODO: Check that keys exist for object $t
            $epitope_sequence = $t[0]->linear_sequence;
        } catch (\Exception $e) {
            $error_message = $e->getMessage();
            Log::error('Epitope: IEDB Epitope request failed for ' . $epitope_id . ': ' . $error_message);
            $epitope_sequence = '';
        }

        return $epitope_sequence;
    }
}
