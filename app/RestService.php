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

    // send "/samples" request to all enabled services
    public static function samples($filters, $username = '')
    {
        $base_uri = 'samples';

        // add username to filters
        $filters['username'] = $username;
        $filters['ir_username'] = $username;

        // time out
        $filters['timeout'] = config('ireceptor.service_request_timeout_samples');

        // prepare parameters for each service
        $request_params = [];
        foreach (self::findEnabled() as $rs) {
            $t = [];

            $t['rs'] = $rs;
            $t['url'] = $rs->url . 'v' . $rs->version . '/' . $base_uri;
            $t['params'] = $filters;

            $request_params[] = $t;
        }

        // do requests
        $response_list = self::doRequests($request_params);

        // add "real" rest_service_id to each sample
        foreach ($response_list as $response) {
            $rs = $response['rs'];

            $sample_list = $response['data'];
            foreach ($sample_list as $sample) {
                // so any query is sent to the proper service
                $sample->real_rest_service_id = $rs->id;
            }

            $response['data'] = $sample_list;
        }

        // merge service responses belonging to the same group
        $response_list_grouped = [];
        foreach ($response_list as $response) {
            $group = $response['rs']->rest_service_group_code;
            // service doesn't belong to a group -> just add the response
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

        $response_list = $response_list_grouped;

        return $response_list;
    }

    // send "/sequences_summary" request to all enabled services
    public static function sequences_summary($filters, $username = '')
    {
        $base_uri = 'sequences_summary';

        // add username to filters
        $filters['username'] = $username;
        $filters['ir_username'] = $username;

        // time out
        $filters['timeout'] = config('ireceptor.service_request_timeout');

        // prepare request parameters for each service
        $request_params = [];
        foreach (self::findEnabled() as $rs) {
            $t = [];

            $sample_id_list_key = 'ir_project_sample_id_list_' . $rs->id;
            if (array_key_exists($sample_id_list_key, $filters) && ! empty($filters[$sample_id_list_key])) {
                // remove REST service id
                // ir_project_sample_id_list_2 -> ir_project_sample_id_list
                $filters['ir_project_sample_id_list'] = $filters[$sample_id_list_key];
                unset($filters[$sample_id_list_key]);
            } else {
                // if no sample id for this REST service, don't query it.
                continue;
            }

            $t['rs'] = $rs;
            $t['url'] = $rs->url . 'v' . $rs->version . '/' . $base_uri;
            $t['params'] = $filters;

            $request_params[] = $t;
        }

        // do requests
        $response_list = self::doRequests($request_params);

        // merge service responses belonging to the same group
        $response_list_grouped = [];
        foreach ($response_list as $response) {
            $group = $response['rs']->rest_service_group_code;
            // service doesn't belong to a group -> just add the response
            if ($group == '') {
                $response_list_grouped[] = $response;
            } else {
                // a response with that group already exists? -> merge
                if (isset($response_list_grouped[$group])) {
                    $r1 = $response_list_grouped[$group];
                    $r2 = $response;

                    // merge data if both responses were sucessful
                    if ($r1['status'] == 'success' && $r2['status'] == 'success') {
                        $r1['data']->summary = array_merge($r1['data']->summary, $r2['data']->summary);
                        $r1['data']->items = array_merge($r1['data']->items, $r2['data']->items);
                    }

                    // merge response status
                    if ($r2['status'] != 'success') {
                        $r1['status'] = $r2['status'];
                        $r1['error_message'] = $r2['error_message'];
                    }

                    $response_list_grouped[$group] = $r1;
                } else {
                    $response_list_grouped[$group] = $response;
                }
            }
        }

        $response_list = $response_list_grouped;

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

        // time out
        $filters['timeout'] = config('ireceptor.service_file_request_timeout');

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

            $t = [];
            $t['rs'] = $rs;
            $t['url'] = $rs->url . 'v' . $rs->version . '/' . $base_uri;
            $t['params'] = $filters;

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
                $timeout = array_get($t, 'params.timeout', config('ireceptor.service_request_timeout'));
                array_forget($t, 'params.timeout');
                $params = array_get($t, 'params', []);

                // build Guzzle request params array
                $options = [];
                $options['auth'] = [$rs->username, $rs->password];
                $options['timeout'] = $timeout;

                // remove null values.
                foreach ($params as $k => $v) {
                    if ($v === null) {
                        unset($params[$k]);
                    }
                }

                // For VDJServer, send array parameters without brackets. Ex: p1=a&p1=b
                if (str_contains($url, 'vdj') || str_contains($url, '206.12.99.176:8080')) {
                    // build query string with special function which doesn't add brackets
                    $queryString = \GuzzleHttp\Psr7\build_query($params, PHP_QUERY_RFC1738);

                    // set request body and header manually
                    $options['body'] = $queryString;
                    $options['headers'] = ['Content-Type' => 'application/x-www-form-urlencoded'];
                } else {
                    // For PHP services, use Guzzle default behaviour. Ex: p1[]=a&p1[]=b
                    $options['form_params'] = $params;
                }

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
                $query_log_id = QueryLog::start_rest_service_query($rs->id, $rs->name, $url, $params, $file_path);

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
                            $response = $exception->getMessage();
                            Log::error($response);
                            QueryLog::end_rest_service_query($query_log_id, '', 'error', $response);

                            $t['status'] = 'error';
                            $t['error_message'] = $response;
                            $t['query_log_id'] = $query_log_id;
                            
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
