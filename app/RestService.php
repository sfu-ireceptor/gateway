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


    // do samples request to all enabled services
    public static function samples($filters, $username = '')
    {
        $base_uri = 'repertoire';

        // override field names from gateway (ir_id) to AIRR (ir_v2)
        $filters = FieldName::convert($filters, 'ir_id', 'ir_v2');

        // prepare parameters for each service
        $request_params = [];
        foreach (self::findEnabled() as $rs) {
            $t = [];

            $t['rs'] = $rs;
            $t['url'] = $rs->url . $base_uri;
            $t['timeout'] = config('ireceptor.service_request_timeout_samples');

            // remove null filter values.
            foreach ($filters as $k => $v) {
                if ($v === null) {
                    unset($filters[$k]);
                }
            }

            // remove gateway filters
            unset($filters['cols']);

            $filters = FieldName::convert($filters, 'ir_id', 'ir_adc_api_query');
            // dd($filters);

            $filter_object_list = [];
            foreach ($filters as $k => $v) {
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
                // $filters_and->content = array_values($filters_and->content);
                // dd($filters_and->content);
                // echo  json_encode($filters_and->content, JSON_PRETTY_PRINT);
                // die();

                $filter_object->filters = $filters_and;
                // $filter_object->filters->content = array_values($filters_and->content);
            }

            // dd($filter_object);

            // $filters['tetewt'] = 32;
            // dd($filters);
            // echo  json_encode($filter_object,  JSON_PRETTY_PRINT);
            // die();
            $t['params'] = json_encode($filter_object);
            // dd($t['params']);

            $request_params[] = $t;
        }

        // do requests
        $response_list = self::doRequests($request_params);
        // dd($response_list);

        foreach ($response_list as $r_key => $response) {
            $rs = $response['rs'];

            if (isset($response['data']->Repertoire)) {
                // add "real" rest_service_id to each sample
                $sample_list = $response['data']->Repertoire;
                foreach ($sample_list as $sample) {
                    // so any query is sent to the proper service
                    $sample->real_rest_service_id = $rs->id;

                    // get sequence count

                }

                $response['data'] = $sample_list;
            } elseif (isset($response['data']->success) && ! $response['data']->success) {
                $response['status'] = 'error';
                $response['error_message'] = $response['data']->message;
                $response['data'] = [];
            } else {
                $response['status'] = 'error';
                $response['error_message'] = 'Malformed response from service';
            }
            $response_list[$r_key] = $response;
        }
        // dd($response_list);

        // // merge service responses belonging to the same group
        // $response_list_grouped = [];
        // foreach ($response_list as $response) {
        //     $group = $response['rs']->rest_service_group_code;

        //     // override field names from AIRR (ir_v2) to gateway (ir_id)
        //     $response['data'] = FieldName::convertObjectList($response['data'], 'ir_v2', 'ir_id');

        //     // service doesn't belong to a group -> just add the response
        //     if ($group == '') {
        //         $response_list_grouped[] = $response;
        //     } else {
        //         // a response with that group already exists? -> merge
        //         if (isset($response_list_grouped[$group])) {
        //             $r1 = $response_list_grouped[$group];
        //             $r2 = $response;

        //             // merge response status
        //             if ($r2['status'] != 'success') {
        //                 $r1['status'] = $r2['status'];
        //             }

        //             // merge list of samples
        //             $r1['data'] = array_merge($r1['data'], $r2['data']);

        //             $response_list_grouped[$group] = $r1;
        //         } else {
        //             $response_list_grouped[$group] = $response;
        //         }
        //     }
        // }

        // $response_list = $response_list_grouped;

        return $response_list;
    }

    public static function sequence_count($filters, $rest_service_id, $sample_id, $username = '')
    {
         $base_uri = 'rearrangement';

        // override field names from gateway (ir_id) to AIRR (ir_v2)
        $filters = FieldName::convert($filters, 'ir_id', 'ir_v2');

        $rs = self::find($rest_service_id);

        // prepare parameters for each service
        $request_params = [];
        $t = [];

        $t['rs'] = $rs;
        $t['url'] = $rs->url . $base_uri;
        $t['timeout'] = config('ireceptor.service_request_timeout_samples');

        // remove null filter values.
        foreach ($filters as $k => $v) {
            if ($v === null) {
                unset($filters[$k]);
            }
        }

        // remove gateway filters
        unset($filters['cols']);

        $filters = FieldName::convert($filters, 'ir_id', 'ir_adc_api_query');


        $filter_object_list = [];
        foreach ($filters as $k => $v) {
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

        // add sample id filter
        $filter_content = new \stdClass();
        $filter_content->field = 'repertoire_id';
        $filter_content->value = [$sample_id];

        $filter = new \stdClass();
        $filter->op = 'in';
        $filter->content = $filter_content;

        $filter_object_list[] = $filter;

        // build filters string
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

        $filter_object->facets = 'repertoire_id';

        // echo  json_encode($filter_object,  JSON_PRETTY_PRINT);
        // die();
        $t['params'] = json_encode($filter_object);

        $request_params[] = $t;

        // do requests
        $response_list = self::doRequests($request_params);
        // dd($response_list);

        $nb_sequences = 0;
        foreach ($response_list as $r_key => $response) {
            $rs = $response['rs'];

            if (isset($response['data']->Rearrangement)) {
                $nb_sequences = $response['data']->Rearrangement[0]->count;
            }
        }

        return $nb_sequences;
    }

    // send "/sequences_summary" request to all enabled services
    public static function sequences_summary($filters, $username = '', $group_by_rest_service = true)
    {
        $base_uri = 'sequences_summary';

        // add username to filters
        $filters['username'] = $username;
        $filters['ir_username'] = $username;

        // prepare request parameters for each service
        $request_params = [];
        foreach (self::findEnabled() as $rs) {
            $t = [];

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

            $t['rs'] = $rs;
            $t['url'] = $rs->url . 'v' . $rs->version . '/' . $base_uri;
            $t['params'] = $service_filters;
            $t['timeout'] = config('ireceptor.service_request_timeout');

            $request_params[] = $t;
        }

        // do requests
        $response_list = self::doRequests($request_params);

        if ($group_by_rest_service) {
            // merge service responses belonging to the same group
            $response_list_grouped = [];
            foreach ($response_list as $response) {

                // validate response format
                $valid = false;
                if ($response['status'] != 'error') {
                    if (isset($response['data']->summary) && is_array($response['data']->summary)) {
                        if (isset($response['data']->items) && is_array($response['data']->items)) {
                            $response_list[] = $response;
                            $valid = true;
                        }
                    }

                    if (! $valid) {
                        $response['status'] = 'error';
                        $response['error_message'] = 'Incorrect response format';

                        $query_log_id = $response['query_log_id'];
                        QueryLog::update_rest_service_query($query_log_id, '', 'error', 'Incorrect response format');

                        $gw_query_log_id = request()->get('query_log_id');
                        $error_message = 'Incorrect service response format';
                        QueryLog::set_gateway_query_status($gw_query_log_id, 'service_error', $error_message);
                    }
                }

                // if an error occured, create empty data structure
                if ($response['status'] == 'error') {
                    $response['data'] = new \stdClass();
                    $response['data']->summary = [];
                    $response['data']->items = [];
                }

                $group = $response['rs']->rest_service_group_code;

                // override field names from AIRR (ir_v2) to gateway (ir_id)
                $response['data']->summary = FieldName::convertObjectList($response['data']->summary, 'ir_v2', 'ir_id');
                $response['data']->items = FieldName::convertObjectList($response['data']->items, 'ir_v2', 'ir_id');

                // service doesn't belong to a group -> just add the response
                if ($group == '') {
                    $response_list_grouped[] = $response;
                } else {
                    // a response with that group already exists? -> merge
                    if (isset($response_list_grouped[$group])) {
                        $r1 = $response_list_grouped[$group];
                        $r2 = $response;

                        // merge data
                        $r1['data']->summary = array_merge($r1['data']->summary, $r2['data']->summary);
                        $r1['data']->items = array_merge($r1['data']->items, $r2['data']->items);

                        // merge response status
                        if ($r2['status'] != 'success') {
                            $r1['status'] = $r2['status'];
                            $r1['error_message'] = $r2['error_message'];
                            $r1['error_type'] = $r2['error_type'];
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

    // send "/sequences_data" request to all enabled services
    // save returned files in $folder_path
    // curl -X POST -H "Content-Type:application/x-www-form-urlencoded" -d "username=titi&ir_username=titi&ir_project_sample_id_list[]=680&ir_data_format=airr" https://ipa.ireceptor.org/v2/sequences_data
    // curl -X POST -d "ir_project_sample_id_list=8961797805343895065-242ac11c-0001-012&ir_data_format=airr" https://vdjserver.org/ireceptor/v2/sequences_data

    public static function sequences_data($filters, $folder_path, $username = '')
    {
        $base_uri = 'sequences_data';

        $now = time();

        // add username to filters
        $filters['username'] = $username;
        $filters['ir_username'] = $username;

        // get list of rest services which will actually be queried
        $rs_list = [];
        foreach (self::findEnabled() as $rs) {
            $sample_id_list_key = 'ir_project_sample_id_list_' . $rs->id;
            if (array_key_exists($sample_id_list_key, $filters) && ! empty($filters[$sample_id_list_key])) {
                $rs_list[$rs->id] = $rs;
            }
        }

        // for groups, keep track of number of services for that group
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
            // remove REST service id
            // ir_project_sample_id_list_2 -> ir_project_sample_id_list
            $filters['ir_project_sample_id_list'] = $filters[$sample_id_list_key];
            unset($filters[$sample_id_list_key]);

            // override field names from gateway (ir_id) to AIRR (ir_v2)
            $filters = FieldName::convert($filters, 'ir_id', 'ir_v2');

            $t = [];
            $t['rs'] = $rs;
            $t['url'] = $rs->url . 'v' . $rs->version . '/' . $base_uri;
            $t['params'] = $filters;
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

                // // remove null values.
                // foreach ($params as $k => $v) {
                //     if ($v === null) {
                //         unset($params[$k]);
                //     }
                // }

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
