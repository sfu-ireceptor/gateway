<?php

namespace App;

use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Psr\Http\Message\ResponseInterface;

class RestService extends Model
{
    protected $table = 'rest_service';

    protected $fillable = [
        'url', 'name', 'username', 'password', 'enabled', 'version',
    ];

    public static function findAvailable($field_list = null)
    {
        $l = static::where('hidden', false)->orderBy('name', 'asc')->get($field_list);

        foreach ($l as $rs) {
            $group_name = RestServiceGroup::nameForCode($rs->rest_service_group_code);

            // add display name
            $rs->display_name = $group_name ? $group_name : $rs->name;
        }

        return $l;
    }

    public static function findEnabled($field_list = null)
    {
        $l = static::where('hidden', false)->where('enabled', true)->orderBy('name', 'asc')->get($field_list);

        foreach ($l as $rs) {
            $group_name = RestServiceGroup::nameForCode($rs->rest_service_group_code);

            // add display name
            $rs->display_name = $group_name ? $group_name : $rs->name;
        }

        return $l;
    }

    public static function generate_json_query($filters, $query_parameters = [])
    {
        // build array of filter clauses
        $filter_list = [];
        foreach ($filters as $k => $v) {
            $filter = new \stdClass();
            $filter->op = 'contains';
            if (is_array($v)) {
                $filter->op = 'in';
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

    // do samples request to all enabled services
    public static function samples($filters, $username = '')
    {
        // remove empty filters
        foreach ($filters as $k => $v) {
            if ($v === null) {
                unset($filters[$k]);
            }
        }

        // rename filters: internal gateway name -> official API name
        $filters = FieldName::convert($filters, 'ir_id', 'ir_adc_api_query');

        // generate filters string (JSON)
        $filters_json = self::generate_json_query($filters);

        // prepare request parameters for all services
        $request_params_all = [];
        foreach (self::findEnabled() as $rs) {
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
                $sample_id_list = [];
                foreach ($sample_list as $sample) {
                    // add rest_service_id to each sample
                    // done here so it's the real service id (not the group id)
                    // so any subsequent query is sent to the right service
                    $sample->real_rest_service_id = $rs->id;

                    // build list of sample ids
                    $sample_id_list[] = $sample->repertoire_id;
                }

                // do sequence count query and add them to the samples
                $sequence_count = self::sequence_count($rs->id, $sample_id_list);
                foreach ($sample_list as $sample) {
                    $sample->ir_sequence_count = $sequence_count[$sample->repertoire_id];
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
    }

    public static function sequence_count($rest_service_id, $sample_id_list, $filters = [])
    {
        // force all sample ids to string
        foreach ($sample_id_list as $k => $v) {
            $sample_id_list[$k] = (string) $v;
        }

        // generate JSON query
        $filters['repertoire_id'] = $sample_id_list;

        $query_parameters = [];
        $query_parameters['facets'] = 'repertoire_id';

        $filters_json = self::generate_json_query($filters, $query_parameters);

        // prepare parameters array
        $t = [];
        $rs = self::find($rest_service_id);
        $t['url'] = $rs->url . 'rearrangement';
        $t['params'] = $filters_json;
        $t['rs'] = $rs;
        $t['timeout'] = config('ireceptor.service_request_timeout_samples');

        // do request
        $response_list = self::doRequests([$t]);
        $facet_list = data_get($response_list, '0.data.Facet', []);

        $sequence_count = [];
        foreach ($facet_list as $facet) {
            $sequence_count[$facet->repertoire_id] = $facet->count;
        }

        // TODO might not be needed because of IR-1484
        // add count = 0
        foreach ($sample_id_list as $sample_id) {
            if (! isset($sequence_count[$sample_id])) {
                $sequence_count[$sample_id] = 0;
            }
        }

        return $sequence_count;
    }

    // send "/sequences_summary" request to all enabled services
    public static function sequences_summary($filters, $username = '', $group_by_rest_service = true)
    {
        // remove null filter values.
        foreach ($filters as $k => $v) {
            if ($v === null) {
                unset($filters[$k]);
            }
        }

        // rename filters: internal gateway name -> official API name
        $filters = FieldName::convert($filters, 'ir_id', 'ir_adc_api_query');

        // build list of sequence filters only (remove sample id filters)
        $sequence_filters = $filters;
        unset($sequence_filters['project_id_list']);
        foreach (self::findEnabled() as $rs) {
            $sample_id_list_key = 'ir_project_sample_id_list_' . $rs->id;
            unset($sequence_filters[$sample_id_list_key]);
        }

        // query each service one by one
        $response_list = [];
        foreach (self::findEnabled() as $rs) {
            $sample_id_list = [];

            $sample_id_list_key = 'ir_project_sample_id_list_' . $rs->id;
            if (isset($filters[$sample_id_list_key])) {
                $sample_id_list = $filters[$sample_id_list_key];
            } else {
                // if no sample id for this service, don't query it.
                continue;
            }

            // retrieve list of samples
            $sample_data = self::samples(['repertoire_id' => $sample_id_list]);
            $sample_list = data_get($sample_data, '0.data', []);

            // get filtered sequence count for each sample
            $sample_list_filtered_count = self::sequence_count($rs->id, $sample_id_list, $sequence_filters);

            foreach ($sample_list as $sample) {
                $sample_id = $sample->repertoire_id;
                $sample->ir_filtered_sequence_count = $sample_list_filtered_count[$sample_id];
            }

            // dd($sample_list);
            $t = [];
            $t['rs'] = $rs;
            $t['data'] = $sample_list;
            $t['status'] = 'sucess';
            $response_list[] = $t;
        }

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
                            if(isset($r2['error_message'])) {
                                $r1['error_message'] = $r2['error_message'];                                
                            }
                            if(isset($r2['error_type'])) {
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

        // remove null filter values.
        foreach ($filters as $k => $v) {
            if ($v === null) {
                unset($filters[$k]);
            }
        }

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

            // override field names from gateway (ir_id) to AIRR (ir_v2)
            $service_filters = FieldName::convert($service_filters, 'ir_id', 'ir_v2');

            // remove extra ir_project_sample_id_list_ fields
            foreach ($service_filters as $key => $value) {
                if (starts_with($key, 'ir_project_sample_id_list_')) {
                    unset($service_filters[$key]);
                }
            }

            // TODO simpler way to do this?
            $service_filters['repertoire_id'] = $service_filters['ir_project_sample_id_list'];
            unset($service_filters['ir_project_sample_id_list']);

            // $sample_id_list = $service_filters['repertoire_id'];
            // unset($service_filters['repertoire_id']);
            // $c = self::sequence_count($rs->id, $sample_id_list, $service_filters);
            // dd($c);
            // // die();
            // // dd($filters);

            // prepare parameters for each service
            $t = [];

            $t['rs'] = $rs;
            $t['url'] = $rs->url . $base_uri;

            $filter_object_list = [];
            foreach ($service_filters as $k => $v) {
                $filter_content = new \stdClass();
                $filter_content->field = $k;
                $filter_content->value = $v;

                $filter = new \stdClass();

                $filter->op = 'contains';
                if (is_array($v)) {
                    $filter->op = 'in';
                }

                $filter->content = $filter_content;

                $filter_object_list[] = $filter;
            }

            $filter_object = new \stdClass();
            if (count($filter_object_list) == 0) {
            } elseif (count($filter_object_list) == 1) {
                $filter_object->filters = $filter_object_list[0];
            } else {
                $filters_and = new \stdClass();
                $filters_and->op = 'and';
                $filters_and->content = [];
                foreach ($filter_object_list as $filter) {
                    $filters_and->content[] = $filter;
                }
                $filter_object->filters = $filters_and;
            }

            $filter_object->from = 0;
            $filter_object->size = $n;
            $filter_object->fields = ['v_call', 'd_call', 'j_call', 'junction_aa'];

            // echo  json_encode($filter_object,  JSON_PRETTY_PRINT);die();
            $t['params'] = json_encode($filter_object);

            $request_params[] = $t;
        }

        // do requests
        $response_list = self::doRequests($request_params);
        // dd($response_list);
        $nb_sequences = data_get($response_list, '0.data.Rearrangement.0.count', 0);

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
    public static function sequences_data($filters, $folder_path, $username = '')
    {
        $now = time();

        // remove null filter values.
        foreach ($filters as $k => $v) {
            if ($v === null) {
                unset($filters[$k]);
            }
        }

        // rename filters: internal gateway name -> official API name
        $filters = FieldName::convert($filters, 'ir_id', 'ir_adc_api_query');

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
            $sample_id_list_key = 'ir_project_sample_id_list_' . $rs->id;
            // rename service id filter and remove other services' filters
            // ir_project_sample_id_list_2 -> repertoire_id
            $filters['repertoire_id'] = $filters[$sample_id_list_key];
            foreach (self::findEnabled() as $rs) {
                $sample_id_list_key = 'ir_project_sample_id_list_' . $rs->id;
                unset($filters[$sample_id_list_key]);
            }

            $query_parameters = [];
            $query_parameters['format'] = 'tsv';

            // generate JSON query
           $filters_json = self::generate_json_query($filters, $query_parameters);

            $t = [];
            $t['rs'] = $rs;
            $t['url'] = $rs->url . 'rearrangement';
            $t['params'] = $filters_json;
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
            $t['file_path'] = $folder_path . '/' . str_slug($rs->display_name) . $file_suffix . '.tsv';
            $request_params[] = $t;
        }

        // do requests, write tsv data to files
        Log::debug('Do TSV requests...');
        $response_list = self::doRequests($request_params);

        return $response_list;
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
                                // echo $json;die();
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
