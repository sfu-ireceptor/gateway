<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Process\Process;

class RestService extends Model
{
    protected $table = 'rest_service';

    protected $fillable = [
        'url', 'name', 'username', 'password', 'enabled', 'version',
    ];

    public $display_name = '';

    public function __call($method, $parameters)
    {
        if ($method == 'find') {
            $rs = parent::__call($method, $parameters);
            $display_name = self::getDisplayName($rs);
            $rs->display_name = $display_name;

            return $rs;
        } else {
            return parent::__call($method, $parameters);
        }
    }

    public function refreshChunkSize()
    {
        $defaults = [];
        $defaults['base_uri'] = $this->url;
        $defaults['verify'] = false;    // accept self-signed SSL certificates

        try {
            $client = new \GuzzleHttp\Client($defaults);

            $response = $client->get('info');
            $body = $response->getBody();
            $json = json_decode($body);

            if (isset($json->max_size)) {
                $this->chunk_size = $json->max_size;
                $this->save();

                return $this->chunk_size;
            }

            return null;
        } catch (\Exception $e) {
            $error_message = $exception->getMessage();
            Log::error($error_message);

            return $error_message;
        }
    }

    public static function getDisplayName($rs)
    {
        $group_name = RestServiceGroup::nameForCode($rs->rest_service_group_code);
        $display_name = $group_name ? $group_name : $rs->name;

        return $display_name;
    }

    /**
     * Returns the services which are enabled.
     *
     * @param  array|null $field_list Fields to fetch. Fetches all fields by default.
     *
     * @return array List of RestService objects
     */
    public static function findEnabled($field_list = ['*'])
    {
        $l = static::where('hidden', false)->where('enabled', true)->orderBy('name', 'asc')->get($field_list);

        foreach ($l as $rs) {
            $group_name = RestServiceGroup::nameForCode($rs->rest_service_group_code);

            // add display name
            $rs->display_name = $group_name ? $group_name : $rs->name;
        }

        return $l;
    }

    /**
     * Returns the services which can be enabled.
     *
     * @param  array|null $field_list Fields to fetch. Fetches all fields by default.
     *
     * @return array List of RestService objects
     */
    public static function findAvailable($field_list = ['*'])
    {
        $l = static::where('hidden', false)->orderBy('name', 'asc')->get($field_list);

        foreach ($l as $rs) {
            $group_name = RestServiceGroup::nameForCode($rs->rest_service_group_code);

            // add display name
            $rs->display_name = $group_name ? $group_name : $rs->name;
        }

        return $l;
    }

    /**
     * Generates a JSON query for an ADC API service.
     *
     * @param  array $filters Example: ["study.study_title" => "Immunoglobulin"]
     * @param  array $query_parameters Example: ["facets" => "repertoire_id"]
     *
     * @return string JSON
     */
    public static function generate_json_query($filters, $query_parameters = [])
    {
        // clean filters
        $filters = self::clean_filters($filters);

        // add "age_unit" filter if "age_min" or "age_max" is set
        if (isset($filters['ir_subject_age_min']) || isset($filters['ir_subject_age_max'])) {
            $filters['age_unit'] = 'year';
        }

        // rename filters: internal gateway id -> ADC API name
        $filters = FieldName::convert($filters, 'ir_id', 'ir_adc_api_query');

        // build array of filter clauses
        $filter_list = [];
        foreach ($filters as $k => $v) {
            $filter = new \stdClass();

            // default -> substring query
            $filter->op = 'contains';

            $field_type = FieldName::getFieldType($k, 'ir_adc_api_query');
            if (is_array($v)) {
                $filter->op = 'in';
            } elseif ($k == 'subject.age_min') {
                $filter->op = '>=';
                $v = (float) $v;
            } elseif ($k == 'subject.age_max') {
                $filter->op = '<=';
                $v = (float) $v;
            } elseif ($field_type == 'boolean') {
                $filter->op = '=';
                $v = filter_var($v, FILTER_VALIDATE_BOOLEAN);
            } elseif ($field_type == 'integer') {
                $filter->op = '=';
                $v = intval($v);
            } elseif ($field_type == 'number') {
                $filter->op = '=';
                $v = (float) $v;
            } elseif ($k == 'subject.sex' || $k == 'v_call' || $k == 'j_call' || $k == 'd_call' || $k == 'v_gene' || $k == 'j_gene' || $k == 'd_gene' || $k == 'v_subgroup' || $k == 'j_subgroup' || $k == 'd_subgroup') {
                $filter->op = '=';
            }

            $filter->content = new \stdClass();
            $filter->content->field = $k;
            $filter->content->value = $v;

            $filter_list[] = $filter;
        }

        // build final filter object
        $filter_object = new \stdClass();
        if (count($filter_list) == 0) {
        } elseif (count($filter_list) == 1) {
            $filter_object->filters = $filter_list[0];
        } else {
            $filter_object->filters = new \stdClass();
            $filter_object->filters->op = 'and';
            $filter_object->filters->content = [];
            foreach ($filter_list as $filter) {
                $filter_object->filters->content[] = $filter;
            }
        }

        // add extra parameters
        foreach ($query_parameters as $key => $value) {
            $filter_object->{$key} = $value;
        }

        // convert filter object to JSON
        $filter_object_json = json_encode($filter_object);
        Log::debug(json_encode($filter_object, JSON_PRETTY_PRINT));

        return $filter_object_json;
    }

    public static function clean_filters($filters)
    {
        // remove empty filters
        foreach ($filters as $k => $v) {
            if ($v === null) {
                unset($filters[$k]);
            }
        }

        // convert VDJ filters
        foreach (['v', 'd', 'j'] as $t) {
            if (isset($filters[$t . '_call'])) {
                $v = $filters[$t . '_call'];
                unset($filters[$t . '_call']);

                if (str_contains($v, '*')) {
                    $filters[$t . '_call'] = $v;
                } elseif (str_contains($v, '-')) {
                    $filters[$t . '_gene'] = $v;
                } else {
                    $filters[$t . '_subgroup'] = $v;
                }
            }
        }

        // remove gateway-specific filters
        unset($filters['cols']);
        unset($filters['open_filter_panel_list']);
        unset($filters['full_text_search']);
        unset($filters['filters_order']);
        unset($filters['sample_query_id']);
        unset($filters['sort_column']);
        unset($filters['sort_order']);
        unset($filters['page']);
        unset($filters['extra_field']);

        return $filters;
    }

    // do samples request to all enabled services
    public static function samples($filters, $username = '', $count_sequences = true, $rest_service_id_list = null, $grouped = true)
    {
        $rest_service_list = [];
        if ($rest_service_id_list === null) {
            $rest_service_list = self::findEnabled();
        } else {
            foreach ($rest_service_id_list as $rest_service_id) {
                $rs = self::find($rest_service_id);
                $rest_service_list[] = $rs;
            }
        }

        // clean filters for services
        $filters = self::clean_filters($filters);

        // generate filters string (JSON)
        $filters_json = self::generate_json_query($filters);

        // prepare request parameters for all services
        $request_params_all = [];
        foreach ($rest_service_list as $rs) {
            $t = [];
            $t['url'] = $rs->url . 'repertoire';
            $t['params'] = $filters_json;
            $t['rs'] = $rs;
            $t['timeout'] = config('ireceptor.service_request_timeout_samples');

            $request_params_all[] = $t;
        }

        // do requests to all services
        $response_list = self::doRequests($request_params_all);

        // tweak responses
        foreach ($response_list as $i => $response) {
            $rs = $response['rs'];

            // if well-formed response
            if (isset($response['data']->Repertoire)) {
                $sample_list = $response['data']->Repertoire;

                // ignore samples which are not objects
                foreach ($sample_list as $j => $sample) {
                    if (! is_object($sample)) {
                        unset($sample_list[$j]);
                        continue;
                    }
                }

                $sample_id_list = [];
                foreach ($sample_list as $sample) {
                    // add rest_service_id to each sample
                    // done here so it's the real service id (not the group id)
                    // so any subsequent query is sent to the right service
                    $sample->real_rest_service_id = $rs->id;

                    // build list of sample ids
                    $sample_id_list[] = $sample->repertoire_id;
                }

                if ($count_sequences) {
                    $sequence_counts = self::sequence_count_from_cache($rs->id, $sample_id_list);

                    foreach ($sample_list as $sample) {
                        $sample->ir_sequence_count = 0;
                        if (isset($sequence_counts[$sample->repertoire_id])) {
                            $sample->ir_sequence_count = $sequence_counts[$sample->repertoire_id];
                        }
                    }

                    // if there was an error
                    if ($sequence_counts == null) {
                        $response['sequence_count_error'] = true;
                    }
                }

                // replace Info/Repertoire by simple list of samples
                $response['data'] = $sample_list;
            } elseif (isset($response['data']->success) && ! $response['data']->success) {
                $response['status'] = 'error';
                $response['error_message'] = $response['data']->message;
                $response['data'] = [];
            } else {
                $response['status'] = 'error';
                $response['error_message'] = 'Malformed response from service';
                $response['data'] = [];
            }
            $response_list[$i] = $response;
        }

        if ($grouped) {
            // group responses belonging to the same group
            $response_list_grouped = [];
            foreach ($response_list as $response) {
                $group = $response['rs']->rest_service_group_code;

                // service doesn't belong to a group -> just add response
                if ($group == '') {
                    $response_list_grouped[] = $response;
                } else {
                    // a response with that group already exists? -> merge
                    if (isset($response_list_grouped[$group])) {
                        $r1 = $response_list_grouped[$group];
                        $r2 = $response;
                        // merge response status
                        if ($r2['status'] != 'success') {
                            $r1['status'] = $r2['status'];
                            $r1['error_message'] = $r2['error_message'];
                        }
                        // merge list of samples
                        $r1['data'] = array_merge($r1['data'], $r2['data']);
                        $response_list_grouped[$group] = $r1;
                    } else {
                        $response_list_grouped[$group] = $response;
                    }
                }
            }

            return $response_list_grouped;
        } else {
            return $response_list;
        }
    }

    public static function sequence_count_from_cache($rest_service_id, $sample_id_list = [])
    {
        $l = SequenceCount::where('rest_service_id', $rest_service_id)->orderBy('updated_at', 'desc')->take(1)->get();

        if (count($l) == 0) {
            return;
        }

        $all_sequence_counts = $l[0]->sequence_counts;
        if (count($sample_id_list) == 0) {
            return $all_sequence_counts;
        }

        $sequence_counts = [];
        foreach ($sample_id_list as $sample_id) {
            if (isset($all_sequence_counts[$sample_id])) {
                $sequence_counts[$sample_id] = $all_sequence_counts[$sample_id];
            } else {
                $sequence_counts[$sample_id] = null;
            }
        }

        return $sequence_counts;
    }

    // $sample_id_list_by_rs: array of rest_service_id => [list of samples ids]
    public static function sequence_count($sample_id_list_by_rs, $filters = [], $use_cache_if_possible = true)
    {
        // clean filters
        $filters = self::clean_filters($filters);

        // hack: use cached total counts if there are no sequence filters
        if (count($filters) == 0 && $use_cache_if_possible) {
            $counts_by_rs = [];
            foreach ($sample_id_list_by_rs as $rs_id => $sample_id_list) {
                $sequence_count = self::sequence_count_from_cache($rs_id, $sample_id_list);
                $counts_by_rs[$rs_id]['samples'] = $sequence_count;
            }

            return $counts_by_rs;
        }

        // prepare request parameters for each service
        $request_params = [];

        foreach ($sample_id_list_by_rs as $rs_id => $sample_id_list) {
            $service_filters = $filters;

            // force all sample ids to string
            foreach ($sample_id_list as $k => $v) {
                $sample_id_list[$k] = (string) $v;
            }

            // generate JSON query
            $service_filters['repertoire_id'] = $sample_id_list;

            $query_parameters = [];
            $query_parameters['facets'] = 'repertoire_id';

            $filters_json = self::generate_json_query($service_filters, $query_parameters);

            // prepare parameters for each service
            $t = [];

            $rs = self::find($rs_id);
            $t['rs'] = $rs;
            $t['url'] = $rs->url . 'rearrangement';

            $t['params'] = $filters_json;
            $t['timeout'] = config('ireceptor.service_request_timeout');

            $request_params[] = $t;
        }

        // do requests
        $response_list = self::doRequests($request_params);

        // build list of sequence count for each sample grouped by repository id
        $counts_by_rs = [];
        foreach ($response_list as $response) {
            $rest_service_id = $response['rs']->id;

            if ($response['status'] == 'error') {
                $counts_by_rs[$rest_service_id]['samples'] = null;
                $counts_by_rs[$rest_service_id]['error_type'] = $response['error_type'];
                continue;
            }

            $facet_list = data_get($response, 'data.Facet', []);
            $sequence_count = [];
            foreach ($facet_list as $facet) {
                $sequence_count[$facet->repertoire_id] = $facet->count;
            }

            // TODO might not be needed because of IR-1484
            // add count = 0
            foreach ($sample_id_list_by_rs[$rest_service_id] as $sample_id) {
                if (! isset($sequence_count[$sample_id])) {
                    $sequence_count[$sample_id] = 0;
                }
            }

            $counts_by_rs[$rest_service_id]['samples'] = $sequence_count;
        }

        return $counts_by_rs;
    }

    public static function sequences_summary($filters, $username = '', $group_by_rest_service = true)
    {
        Log::debug('RestService::sequences_summary()');

        Log::debug('Filters:');
        Log::debug($filters);

        // build list of repository ids to query
        $rest_service_id_list = [];
        foreach (self::findEnabled() as $rs) {
            $sample_id_list_key = 'ir_project_sample_id_list_' . $rs->id;
            if (isset($filters[$sample_id_list_key])) {
                $rest_service_id_list[] = $rs->id;
            }
        }

        Log::debug('List of repositories (ids) to query:');
        Log::debug($rest_service_id_list);

        // get ALL samples from repositories
        // so we don't have to send the FULL list of samples ids
        // because VDJServer can't handle it
        $response_list_all = self::samples([], $username, true, $rest_service_id_list, false);
        Log::debug('All samples from those repositories:');
        // Log::debug($response_list_all);

        // filter repositories responses to only requested samples
        $response_list_requested = [];
        foreach ($response_list_all as $response) {
            $rs = $response['rs'];

            // build requested list of sample ids for this repository
            $sample_id_list_key = 'ir_project_sample_id_list_' . $rs->id;
            $sample_id_list = $filters[$sample_id_list_key];

            // filter samples
            $sample_list_requested = [];
            foreach ($response['data'] as $sample) {
                if (in_array($sample->repertoire_id, $sample_id_list)) {
                    $sample_list_requested[] = $sample;
                }
            }

            // update repository response
            $response['data'] = $sample_list_requested;
            $response_list_requested[] = $response;
        }

        Log::debug('Filtered to requested samples only:');
        // Log::debug($response_list_requested);

        // build list of sequence filters only (remove sample id filters)
        $sequence_filters = $filters;
        unset($sequence_filters['project_id_list']);
        foreach (self::findEnabled() as $rs) {
            $sample_id_list_key = 'ir_project_sample_id_list_' . $rs->id;
            unset($sequence_filters[$sample_id_list_key]);
        }

        Log::debug('Sequence filters (only):');
        Log::debug($sequence_filters);

        // build list of samples ids to query for each repository
        $sample_id_list_by_rs = [];
        foreach ($response_list_requested as $response) {
            $sample_id_list_requested = [];
            foreach ($response['data'] as $sample) {
                $sample_id_list_requested[] = $sample->repertoire_id;
            }

            $sample_id_list_by_rs[$response['rs']->id] = $sample_id_list_requested;
        }

        Log::debug('Sample ids for each repository:');
        Log::debug($sample_id_list_by_rs);

        // count sequences for each requested sample
        $counts_by_rs = self::sequence_count($sample_id_list_by_rs, $sequence_filters);

        Log::debug('Sequence count for each requested sample (by repository):');
        Log::debug($counts_by_rs);

        // add sequences count to samples
        $response_list_filtered = [];
        foreach ($response_list_requested as $response) {
            $rs = $response['rs'];

            // if there was an error with the repertoire query
            // include this response so the error is reported
            if ($response['status'] == 'error') {
                $response_list_filtered[] = $response;
                continue;
            }

            if ($counts_by_rs[$rs->id]['samples'] == null) {
                $response['status'] = 'error';
                $response['error_type'] = $counts_by_rs[$rs->id]['error_type'];

                // include this response so the error is reported
                $response_list_filtered[] = $response;

                continue;
            }

            $sample_list_filtered = [];
            foreach ($response['data'] as $sample) {
                $sample_count = $counts_by_rs[$rs->id]['samples'][$sample->repertoire_id];
                // include sample only if it has sequences matching the query
                if ($sample_count > 0) {
                    $sample->ir_filtered_sequence_count = $sample_count;
                    $sample_list_filtered[] = $sample;
                }
            }

            // include repository only if it has samples with sequences matching the query
            if (count($sample_list_filtered) > 0) {
                $response['data'] = $sample_list_filtered;
                $response_list_filtered[] = $response;
            }
        }

        $response_list = $response_list_filtered;

        if ($group_by_rest_service) {
            // merge service responses belonging to the same group
            $response_list_grouped = [];
            foreach ($response_list as $response) {
                $group = $response['rs']->rest_service_group_code;

                // service doesn't belong to a group -> just add response
                if ($group == '') {
                    $response_list_grouped[] = $response;
                } else {
                    // a response with that group already exists? -> merge
                    if (isset($response_list_grouped[$group])) {
                        $r1 = $response_list_grouped[$group];
                        $r2 = $response;

                        // merge data
                        $r1['data'] = array_merge($r1['data'], $r2['data']);

                        // merge response status
                        if ($r2['status'] != 'success') {
                            $r1['status'] = $r2['status'];
                            if (isset($r2['error_message'])) {
                                $r1['error_message'] = $r2['error_message'];
                            }
                            if (isset($r2['error_type'])) {
                                $r1['error_type'] = $r2['error_type'];
                            }
                        }

                        $response_list_grouped[$group] = $r1;
                    } else {
                        $response_list_grouped[$group] = $response;
                    }
                }
            }

            $response_list = $response_list_grouped;
        }

        return $response_list;
    }

    // retrieves n sequences
    public static function sequence_list($filters, $n = 10)
    {
        $base_uri = 'rearrangement';

        // prepare request parameters for each service
        $request_params = [];
        foreach (self::findEnabled() as $rs) {
            $service_filters = $filters;

            $sample_id_list_key = 'ir_project_sample_id_list_' . $rs->id;
            if (array_key_exists($sample_id_list_key, $service_filters) && ! empty($service_filters[$sample_id_list_key])) {
                // remove REST service id
                // ir_project_sample_id_list_2 -> ir_project_sample_id_list
                $service_filters['ir_project_sample_id_list'] = $service_filters[$sample_id_list_key];
                unset($service_filters[$sample_id_list_key]);
            } else {
                // if no sample id for this REST service, don't query it.
                continue;
            }

            // remove extra ir_project_sample_id_list_ fields
            foreach ($service_filters as $key => $value) {
                if (starts_with($key, 'ir_project_sample_id_list_')) {
                    unset($service_filters[$key]);
                }
            }

            // clean filters
            $service_filters = self::clean_filters($service_filters);

            $service_filters['repertoire_id'] = $service_filters['ir_project_sample_id_list'];
            unset($service_filters['ir_project_sample_id_list']);

            // if no sequence filters, query only subset of repertoires
            if (count($service_filters) == 1) {
                $service_filters['repertoire_id'] = array_slice($service_filters['repertoire_id'], 0, 20);
            }

            // prepare parameters for each service
            $t = [];

            $t['rs'] = $rs;
            $t['url'] = $rs->url . $base_uri;

            $params = [];
            $params['from'] = 0;
            $params['size'] = $n;

            $filters_json = self::generate_json_query($service_filters, $params);
            $t['params'] = $filters_json;

            $request_params[] = $t;
        }

        // do requests
        $response_list = self::doRequests($request_params);
        // dd($response_list);
        $nb_sequences = data_get($response_list, '0.data.Rearrangement.0.count', 0);

        return $response_list;
    }

    // curl -i https://stats-staging.ireceptor.org/irplus/v1/stats/rearrangement/gene_usage
    // {
    //     "repertoires":[{"repertoire":{"repertoire_id":"322"}},{"repertoire":{"repertoire_id": "279"}}],
    //     "statistics":["v_call_unique", "v_gene_unique", "v_subgroup_unique"]
    // }
    public static function stats($rest_service_id, $repertoire_id, $stat)
    {
        // $str = file_get_contents("/home/vagrant/ireceptor_gateway/public/test_data/gene2.json");
        // return $str;

        // build stats URL to query
        $rs = self::find($rest_service_id);
        $rs_base_url = str_replace('airr/v1/', '', $rs->url);
        $rs_stats_url = $rs_base_url . 'irplus/v1/stats/rearrangement/';

        // create Guzzle client
        $defaults = [];
        $defaults['verify'] = false;    // accept self-signed SSL certificates
        $defaults['headers'] = ['Content-Type' => 'application/x-www-form-urlencoded'];
        $client = new \GuzzleHttp\Client($defaults);

        $repertoire_object = new \stdClass();
        $repertoire_object->repertoire = new \stdClass();
        $repertoire_object->repertoire->repertoire_id = $repertoire_id;
        $repertoire_list = [];
        $repertoire_list[] = $repertoire_object;

        $statistics_list = [];

        if ($stat == 'v_gene_usage') {
            $url = $rs_stats_url . 'gene_usage';
            $statistics_list[] = 'v_call_unique';
            $statistics_list[] = 'v_gene_unique';
            $statistics_list[] = 'v_subgroup_unique';
        } elseif ($stat == 'd_gene_usage') {
            $url = $rs_stats_url . 'gene_usage';
            $statistics_list[] = 'd_call_unique';
            $statistics_list[] = 'd_gene_unique';
            $statistics_list[] = 'd_subgroup_unique';
        } elseif ($stat == 'j_gene_usage') {
            $url = $rs_stats_url . 'gene_usage';
            $statistics_list[] = 'j_call_unique';
            $statistics_list[] = 'j_gene_unique';
            $statistics_list[] = 'j_subgroup_unique';
        } elseif ($stat == 'junction_length_stats') {
            $url = $rs_stats_url . 'junction_length';
            $statistics_list[] = 'junction_length';
            $statistics_list[] = 'junction_aa_length';
            $statistics_list[] = 'junction_length_productive';
            $statistics_list[] = 'junction_aa_length_productive';
        } elseif ($stat == 'count_stats') {
            $url = $rs_stats_url . 'count';
            $statistics_list[] = 'rearrangement_count';
            $statistics_list[] = 'rearrangement_count_productive';
            $statistics_list[] = 'duplicate_count';
            $statistics_list[] = 'duplicate_count_productive';
        } else {
            Log::error('Unknown stat:' . $stat);
        }

        Log::debug('Stats URL :' . $url);

        $filter_object = new \stdClass();
        $filter_object->repertoires = $repertoire_list;
        $filter_object->statistics = $statistics_list;

        $filter_object_json = json_encode($filter_object);
        Log::debug('Stats JSON request: ' . json_encode($filter_object, JSON_PRETTY_PRINT));

        $response = $client->request('POST', $url, [
            'body' => $filter_object_json,
        ]);

        return $response->getBody();
    }

    public static function sample_list_repertoire_data($filters, $folder_path, $username = '')
    {
        $now = time();

        // build list of services to query
        $rs_list = [];
        foreach (self::findEnabled() as $rs) {
            $sample_id_list_key = 'ir_project_sample_id_list_' . $rs->id;
            if (array_key_exists($sample_id_list_key, $filters) && ! empty($filters[$sample_id_list_key])) {
                $rs_list[$rs->id] = $rs;
            }
        }

        // count services in each service group
        $group_list = [];
        foreach ($rs_list as $rs) {
            $group = $rs->rest_service_group_code;
            if ($group) {
                if (! isset($group_list[$group])) {
                    $group_list[$group] = 0;
                }
                $group_list[$group] += 1;
            }
        }

        // prepare request parameters for each service
        $request_params = [];
        $group_list_count = [];
        foreach ($rs_list as $rs) {
            $rs_filters = [];
            $sample_id_list_key = 'ir_project_sample_id_list_' . $rs->id;
            // rename service id filter and remove other services' filters
            // ir_project_sample_id_list_2 -> repertoire_id
            $rs_filters['repertoire_id'] = $filters[$sample_id_list_key];

            // remove extra ir_project_sample_id_list_ fields
            foreach ($rs_filters as $key => $value) {
                if (starts_with($key, 'ir_project_sample_id_list_')) {
                    unset($rs_filters[$key]);
                }
            }

            $query_parameters = [];

            // generate JSON query
            $rs_filters_json = self::generate_json_query($rs_filters, $query_parameters);

            $t = [];
            $t['rs'] = $rs;
            $t['url'] = $rs->url . 'repertoire';
            $t['params'] = $rs_filters_json;
            $t['timeout'] = config('ireceptor.service_file_request_timeout');

            // add number suffix for rest services belonging to the same group
            $file_suffix = '';
            $group = $rs->rest_service_group_code;
            if ($group && $group_list[$group] >= 1) {
                if (! isset($group_list_count[$group])) {
                    $group_list_count[$group] = 0;
                }
                $group_list_count[$group] += 1;
                $file_suffix = '-' . $group_list_count[$group];
            }
            $t['file_path'] = $folder_path . '/' . str_slug($rs->display_name) . $file_suffix . '-metadata.json';
            $request_params[] = $t;
        }

        // do requests, write tsv data to files
        Log::debug('Do metadata files for TSV requests...');
        $response_list = self::doRequests($request_params);

        return $response_list;
    }

    public static function repertoire_data($filters, $folder_path, $username = '')
    {
        $now = time();

        // build list of services to that we'll create a JSON file for (those that do have results for those filters)
        $rs_list = [];
        $response_list = self::samples($filters, $username, false);
        foreach ($response_list as $i => $response) {
            $sample_list = $response['data'];
            foreach ($sample_list as $sample) {
                $rest_service_id = $sample->real_rest_service_id;
                $rs = self::find($rest_service_id);
                $rs_list[$rs->id] = $rs;
            }
        }

        // count services in each service group
        $group_list = [];
        foreach ($rs_list as $rs) {
            $group = $rs->rest_service_group_code;
            if ($group) {
                if (! isset($group_list[$group])) {
                    $group_list[$group] = 0;
                }
                $group_list[$group] += 1;
            }
        }

        // prepare request parameters for each service
        $request_params = [];
        $group_list_count = [];
        foreach ($rs_list as $rs) {
            $query_parameters = [];

            // generate JSON query
            $rs_filters_json = self::generate_json_query($filters, $query_parameters);

            $t = [];
            $t['rs'] = $rs;
            $t['url'] = $rs->url . 'repertoire';
            $t['params'] = $rs_filters_json;
            $t['timeout'] = config('ireceptor.service_file_request_timeout');

            // add number suffix for rest services belonging to the same group
            $file_suffix = '';
            $group = $rs->rest_service_group_code;
            if ($group && $group_list[$group] >= 1) {
                if (! isset($group_list_count[$group])) {
                    $group_list_count[$group] = 0;
                }
                $group_list_count[$group] += 1;
                $file_suffix = '-' . $group_list_count[$group];
            }
            $t['file_path'] = $folder_path . '/' . str_slug($rs->display_name) . $file_suffix . '-metadata.json';
            $request_params[] = $t;
        }

        // do requests, write tsv data to files
        Log::debug('Do metadata files for TSV requests...');
        $response_list = self::doRequests($request_params);

        return $response_list;
    }

    // retrieve TSV sequence data from enabled services
    // save returned files in $folder_path
    // Example:
    // curl -k -i --data @test.json https://206.12.89.109/airr/v1/rearrangement
    // {
    //   "filters": {
    //     "op": "in",
    //     "content": {
    //       "field": "repertoire_id",
    //       "value": [
    //         "12"
    //       ]
    //     }
    //   },
    //   "format": "tsv"
    // }
    public static function sequences_data($filters, $folder_path, $username = '', $expected_nb_sequences_by_rs)
    {
        $now = time();

        // build list of services to query
        $rs_list = [];
        foreach (self::findEnabled() as $rs) {
            if (isset($expected_nb_sequences_by_rs[$rs->id])) {
                $rs_list[$rs->id] = $rs;
            }
        }

        // count services in each service group
        $group_list = [];
        foreach ($rs_list as $rs) {
            $group = $rs->rest_service_group_code;
            if ($group) {
                if (! isset($group_list[$group])) {
                    $group_list[$group] = 0;
                }
                $group_list[$group] += 1;
            }
        }

        // prepare request parameters for each service
        $request_params = [];
        $request_params_chunking = [];

        $group_list_count = [];
        foreach ($rs_list as $rs) {
            $rs_filters = $filters;
            $sample_id_list_key = 'ir_project_sample_id_list_' . $rs->id;

            // rename sample id filter for this service:
            // ir_project_sample_id_list_2 -> repertoire_id
            $rs_filters['repertoire_id'] = $filters[$sample_id_list_key];

            // remove "ir_project_sample_id_list_*" filters
            foreach ($rs_filters as $key => $value) {
                if (starts_with($key, 'ir_project_sample_id_list_')) {
                    unset($rs_filters[$key]);
                }
            }

            $query_parameters = [];
            $query_parameters['format'] = 'tsv';

            if (isset($rs->chunk_size) && ($rs->chunk_size != null)) {
                $chunk_size = $rs->chunk_size;
                $nb_results = $expected_nb_sequences_by_rs[$rs->id];
                $nb_chunks = (int) ceil($nb_results / $chunk_size);
                for ($i = 0; $i < $nb_chunks; $i++) {
                    $from = $i * $chunk_size;
                    $size = min($chunk_size, $nb_results - ($i * $chunk_size));
                    $query_parameters['from'] = $from;
                    $query_parameters['size'] = $size;

                    // generate JSON query
                    $rs_filters_json = self::generate_json_query($rs_filters, $query_parameters);

                    $t = [];
                    $t['rs'] = $rs;
                    $t['url'] = $rs->url . 'rearrangement';
                    $t['params'] = $rs_filters_json;
                    $t['timeout'] = config('ireceptor.service_file_request_chunked_timeout');

                    // add number suffix for rest services belonging to a group
                    $file_suffix = '';
                    $group = $rs->rest_service_group_code;
                    if ($group && $group_list[$group] >= 1) {
                        if (! isset($group_list_count[$group])) {
                            $group_list_count[$group] = 0;
                        }
                        $group_list_count[$group] += 1;
                        $file_suffix = '-' . $group_list_count[$group];
                    }
                    $t['file_path'] = $folder_path . '/' . str_slug($rs->display_name) . $file_suffix . '_' . $i . '.tsv';
                    $request_params_chunking[] = $t;
                }
            } else {
                // generate JSON query
                $rs_filters_json = self::generate_json_query($rs_filters, $query_parameters);

                $t = [];
                $t['rs'] = $rs;
                $t['url'] = $rs->url . 'rearrangement';
                $t['params'] = $rs_filters_json;
                $t['timeout'] = config('ireceptor.service_file_request_timeout');

                // add number suffix for rest services belonging to a group
                $file_suffix = '';
                $group = $rs->rest_service_group_code;
                if ($group && $group_list[$group] >= 1) {
                    if (! isset($group_list_count[$group])) {
                        $group_list_count[$group] = 0;
                    }
                    $group_list_count[$group] += 1;
                    $file_suffix = '-' . $group_list_count[$group];
                }
                $t['file_path'] = $folder_path . '/' . str_slug($rs->display_name) . $file_suffix . '.tsv';
                $request_params[] = $t;
            }
        }

        $final_response_list = [];
        // do requests, write tsv data to files
        if (count($request_params) > 0) {
            Log::info('Do TSV requests... (not-chunked)');
            $final_response_list = self::doRequests($request_params);
        }

        if (count($request_params_chunking) > 0) {
            Log::info('Do TSV requests... (chunked)');
            $request_params_chunked = array_chunk($request_params_chunking, 15);
            $response_list = [];
            $failed = false;
            foreach ($request_params_chunked as $requests) {

                // try each group of queries up to 3 times
                for ($i = 1; $i <= 3; $i++) {
                    $response_list_chunk = self::doRequests($requests);

                    $has_errors = false;

                    foreach ($response_list_chunk as $response) {
                        if ($response['status'] == 'error') {
                            $has_errors = true;
                        }
                    }

                    if (! $has_errors) {
                        break;
                    }

                    if ($has_errors && $i == 3) {
                        $failed = true;
                        break;
                    }
                }

                $response_list[] = $response_list_chunk;

                if ($failed) {
                    break;
                }
            }

            if ($failed) {
                $response = $response_list[0][0];
            } else {
                $output_files = [];
                foreach ($response_list as $response_group) {
                    foreach ($response_group as $response) {
                        $file_path = $response['data']['file_path'];
                        $output_files[] = $file_path;
                    }
                }
                $output_files_str = implode(' ', $output_files);
                $file_path_merged = $folder_path . '/' . str_slug($rs->display_name) . $file_suffix . '.tsv';

                Log::info('Merging chunked files...');
                $cmd = base_path() . '/util/scripts/airr-tsv-merge.py -i ' . $folder_path . '/' . str_slug($rs->display_name) . $file_suffix . '_*.tsv' . ' -o ' . $file_path_merged . ' 2>&1';
                Log::info($cmd);
                $process = new Process($cmd);
                $process->setTimeout(3600 * 24);
                $process->mustRun(function ($type, $buffer) {
                    Log::info($buffer);
                });

                Log::info('Deleting chunked files...');
                foreach ($output_files as $output_file_path) {
                    if (File::exists($output_file_path)) {
                        File::delete($output_file_path);
                    }
                }

                $response = $response_list[0][0];
                $response['data']['file_path'] = $file_path_merged;
            }

            $final_response_list[] = $response;
        }

        return $final_response_list;
    }

    // do requests (in parallel)
    public static function doRequests($request_params)
    {
        // create Guzzle client
        $defaults = [];
        $defaults['verify'] = false;    // accept self-signed SSL certificates
        $client = new \GuzzleHttp\Client($defaults);

        // prepare requests
        $iterator = function () use ($client, $request_params) {
            foreach ($request_params as $t) {
                // get request params values
                $url = array_get($t, 'url', []);
                $file_path = array_get($t, 'file_path', '');
                $returnArray = array_get($t, 'returnArray', false);
                $rs = array_get($t, 'rs');
                $timeout = array_get($t, 'timeout', config('ireceptor.service_request_timeout'));
                array_forget($t, 'params.timeout');
                $params_str = array_get($t, 'params', '{}');

                // build Guzzle request params array
                $options = [];
                $options['auth'] = [$rs->username, $rs->password];
                $options['timeout'] = $timeout;

                $options['headers'] = ['Content-Type' => 'application/x-www-form-urlencoded'];
                $options['body'] = $params_str;

                if ($file_path != '') {
                    $dirPath = dirname($file_path);
                    if (! is_dir($dirPath)) {
                        Log::info('Creating directory ' . $dirPath);
                        mkdir($dirPath, 0755, true);
                    }

                    $options['sink'] = fopen($file_path, 'a');
                    Log::info('Guzzle: saving to ' . $file_path);
                }

                $t = [];
                $t['rs'] = $rs;
                $t['status'] = 'success';
                $t['data'] = [];

                // execute request
                $query_log_id = QueryLog::start_rest_service_query($rs->id, $rs->name, $url, $params_str, $file_path);

                yield $client
                    ->requestAsync('POST', $url, $options)
                    ->then(
                        function (ResponseInterface $response) use ($query_log_id, $file_path, $returnArray, $t) {
                            if ($file_path == '') {
                                QueryLog::end_rest_service_query($query_log_id);

                                // return object generated from json response
                                $json = $response->getBody();
                                // Log::debug($json);
                                $obj = json_decode($json, $returnArray);
                                $t['data'] = $obj;
                                $t['query_log_id'] = $query_log_id;

                                return $t;
                            } else {
                                QueryLog::end_rest_service_query($query_log_id, filesize($file_path));

                                $t['data']['file_path'] = $file_path;
                                $t['query_log_id'] = $query_log_id;

                                return $t;
                            }
                        },
                        function ($exception) use ($query_log_id, $t) {
                            // log error
                            $response = $exception->getMessage();
                            Log::error($response);
                            QueryLog::end_rest_service_query($query_log_id, '', 'error', $response);

                            $t['status'] = 'error';
                            $t['error_message'] = $response;
                            $t['query_log_id'] = $query_log_id;
                            $t['error_type'] = 'error';
                            $error_class = get_class_name($exception);
                            if ($error_class == 'ConnectException') {
                                $t['error_type'] = 'timeout';
                            }

                            // update gateway user query status
                            // note: a bit hacky (knows about request()) but simpler like that
                            $gw_query_log_id = QueryLog::get_query_log_id();
                            QueryLog::set_gateway_query_status($gw_query_log_id, 'service_error', $t['error_message']);

                            return $t;
                        }
                    );
            }
        };

        // send requests
        $response_list = [];
        $promise = \GuzzleHttp\Promise\each_limit(
            $iterator(),
            15, // set maximum number of requests that can be done at the same time
            function ($response, $i) use (&$response_list) {
                $response_list[$i] = $response;
            }
        );

        // wait for all requests to finish
        $promise->wait();

        return $response_list;
    }
}
