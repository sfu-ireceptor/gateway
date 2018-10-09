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

    // return list of enabled services
    public static function findEnabled($filters = null)
    {
        $l = static::where('enabled', '=', true)->orderBy('name', 'asc')->get($filters);

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
        $options['timeout'] = config('ireceptor.service_request_timeout_samples');

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
        $options['timeout'] = config('ireceptor.service_request_timeout');

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

        // prepare request parameters for each service
        $request_params = [];
        foreach (self::findEnabled() as $rs) {
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

            $t = [];
            $t['rs'] = $rs;
            $t['url'] = $rs->url . 'v' . $rs->version . '/' . $base_uri;
            $t['params'] = $filters;
            $t['file_path'] = $folder_path . '/' . str_slug($rs->name) . '.tsv';

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
                $params = array_get($t, 'params', []);
                $file_path = array_get($t, 'file_path', '');
                $returnArray = array_get($t, 'returnArray', false);
                $rs = array_get($t, 'rs');

                // build Guzzle request params array
                $options = [];
                $options['auth'] = [$rs->username, $rs->password];

                if (isset($request_params['timeout'])) {
                    $options['timeout'] = $request_params['timeout'];
                }
                unset($request_params['timeout']);

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

                                return $t;
                            } else {
                                QueryLog::end_rest_service_query($query_log_id, filesize($file_path));

                                $t['data']['file_path'] = $file_path;

                                return $t;
                            }
                        },
                        function ($exception) use ($query_log_id, $t) {
                            $response = $exception->getMessage();
                            Log::error($response);
                            QueryLog::end_rest_service_query($query_log_id, '', 'error', $response);

                            $t['status'] = 'error';
                            $t['error_message'] = $response;

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
