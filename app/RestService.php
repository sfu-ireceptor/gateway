<?php

namespace App;

use ZipArchive;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
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
    public static function samples($filters)
    {
        $base_uri = 'samples';

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
    public static function sequences_summary($filters)
    {
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

            $uri = 'v2/sequences_summary';

            $t['rs'] = $rs;
            $t['url'] = $rs->url . $uri;
            $t['params'] = $filters;

            $request_params[] = $t;
        }

        // do requests
        $response_list = self::doRequests($request_params);

        return $response_list;
    }

    public static function sequencesTSV($filters, $username, $query_log_id, $url, $sample_filters = [])
    {
        // allow more time than usual for this request
        set_time_limit(config('ireceptor.gateway_file_request_timeout'));

        $filters = self::sanitize_filters($filters);

        // create receiving folder
        $storage_folder = storage_path() . '/app/public/';
        $now = time();
        $time_str = date('Y-m-d_Hi', $now);
        $folder_name = 'ir_' . $time_str . '_' . uniqid();
        $folder_path = $storage_folder . $folder_name;
        File::makeDirectory($folder_path, 0777, true, true);

        // add username to filters
        $filters['username'] = $username;
        $filters['ir_username'] = $username;

        $filters['output'] = 'tsv';
        $filters['ir_data_format'] = 'airr';

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

            $uri = 'v2/sequences_data';

            $t = [];
            $t['rs'] = $rs;
            $t['url'] = $rs->url . $uri;
            $t['params'] = $filters;
            $t['file_path'] = $folder_path . '/' . str_slug($rs->name) . '.tsv';
            $t['gw_query_log_id'] = $query_log_id;

            $request_params[] = $t;
        }

        // do requests, write tsv data to files
        Log::debug('Do TSV requests...');
        $response_list = self::doRequests($request_params);

        // get stats about files
        Log::debug('Get TSV files stats');
        $file_stats = [];
        foreach ($response_list as $response) {
            if (isset($response['data']['file_path'])) {
                $t = [];
                $file_path = $response['data']['file_path'];
                $t['name'] = basename($file_path);
                $t['size'] = human_filesize($file_path);

                // count number of lines
                $n = 0;
                $f = fopen($file_path, 'r');
                while (! feof($f)) {
                    $line = fgets($f);
                    if (! empty(trim($line))) {
                        $n++;
                    }
                }
                fclose($f);
                $t['nb_sequences'] = $n - 1; // remove count of headers line
                $file_stats[] = $t;
            }
        }

        // add info.txt
        $s = '';
        $s .= '* Summary *' . "\n";

        $nb_sequences_total = 0;
        foreach ($file_stats as $t) {
            $nb_sequences_total += $t['nb_sequences'];
        }
        $s .= 'Total: ' . $nb_sequences_total . ' sequences' . "\n";

        foreach ($file_stats as $t) {
            $s .= $t['name'] . ': ' . $t['nb_sequences'] . ' sequences (' . $t['size'] . ')' . "\n";
        }
        $s .= "\n";

        $s .= '* Metadata filters *' . "\n";
        Log::debug($sample_filters);
        if (count($sample_filters) == 0) {
            $s .= 'None' . "\n";
        }
        foreach ($sample_filters as $k => $v) {
            if (is_array($v)) {
                $v = implode(' or ', $v);
            }
            // use human-friendly filter name
            $s .= __('short.' . $k) . ': ' . $v . "\n";
        }
        $s .= "\n";

        $s .= '* Sequence filters *' . "\n";
        unset($filters['ir_project_sample_id_list']);
        unset($filters['cols']);
        unset($filters['filters_order']);
        unset($filters['sample_query_id']);
        unset($filters['open_filter_panel_list']);
        unset($filters['username']);
        unset($filters['ir_username']);
        unset($filters['ir_data_format']);
        unset($filters['output']);
        unset($filters['tsv']);
        foreach ($filters as $k => $v) {
            if ($v === null) {
                unset($filters[$k]);
            }
        }

        Log::debug($filters);
        if (count($filters) == 0) {
            $s .= 'None' . "\n";
        }
        foreach ($filters as $k => $v) {
            if (is_array($v)) {
                $v = implode(' or ', $v);
            }
            // use human-friendly filter name
            $s .= __('short.' . $k) . ': ' . $v . "\n";
        }
        $s .= "\n";

        $s .= '* Source *' . "\n";
        $s .= $url . "\n";
        $date_str_human = date('M j, Y', $now);
        $time_str_human = date('H:i T', $now);
        $s .= 'Downloaded by ' . $username . ' on ' . $date_str_human . ' at ' . $time_str_human . "\n";

        $info_file_path = $folder_path . '/info.txt';
        file_put_contents($info_file_path, $s);

        // zip files
        $zipPath = $folder_path . '.zip';
        Log::info('Zip files to ' . $zipPath);
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE);
        foreach ($response_list as $response) {
            if (isset($response['file_path'])) {
                $file_path = $response['file_path'];
                $zip->addFile($file_path, basename($file_path));
            }
        }
        $zip->addFile($info_file_path, basename($info_file_path));
        $zip->close();

        // delete files
        Log::debug('Delete downloaded files...');
        foreach ($response_list as $response) {
            $file_path = $response['data']['file_path'];
            File::delete($file_path);
        }
        File::delete($info_file_path);

        // delete containing folder
        rmdir($folder_path);

        $zipPublicPath = 'storage' . str_after($folder_path, storage_path('app/public')) . '.zip';

        $t = [];
        $t['size'] = filesize($zipPath);
        $t['system_path'] = $zipPath;
        $t['public_path'] = $zipPublicPath;

        return $t;
    }

    public static function search_samples($sample_filters, $username, $query_log_id)
    {
        // get samples
        $sample_data = self::samples($sample_filters, $username, $query_log_id);
        $sample_list = $sample_data['items'];

        // get samples ids
        $sample_id_filters = [];
        foreach ($sample_list as $sample) {
            $sample_id_filters['ir_project_sample_id_list_' . $sample->rest_service_id][] = $sample->ir_project_sample_id;
        }

        return $sample_id_filters;
    }

    public static function search($sample_filters, $sequence_filters, $username, $query_log_id)
    {
        $sample_filters = self::sanitize_filters($sample_filters);
        $sample_id_filters = self::search_samples($sample_filters, $username, $query_log_id);

        // get sequences summary
        $sequence_filters = array_merge($sequence_filters, $sample_id_filters);
        $sequence_filters = self::sanitize_filters($sequence_filters);
        $sequence_data = self::sequences_summary($sequence_filters, $username, $query_log_id);

        return $sequence_data;
    }

    public static function sanitize_filters($filters)
    {
        if (isset($filters['v_call'])) {
            $filters['v_call'] = strtoupper($filters['v_call']);
        }

        if (isset($filters['j_call'])) {
            $filters['j_call'] = strtoupper($filters['j_call']);
        }

        if (isset($filters['d_call'])) {
            $filters['d_call'] = strtoupper($filters['d_call']);
        }

        if (isset($filters['junction_aa'])) {
            $filters['junction_aa'] = strtoupper($filters['junction_aa']);
        }

        return $filters;
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
                $gw_query_log_id = array_get($t, 'gw_query_log_id', '');
                $rs = array_get($t, 'rs');

                // build Guzzle request params array
                $options = [];
                $options['auth'] = [$rs->username, $rs->password];

                if ($file_path == '') {
                    $options['timeout'] = config('ireceptor.service_request_timeout');
                } else {
                    $options['timeout'] = config('ireceptor.service_file_request_timeout');
                }

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
                $query_log_id = QueryLog::start_rest_service_query($gw_query_log_id, $rs->id, $rs->name, $url, $params, $file_path);

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
                        function ($exception) use ($gw_query_log_id, $query_log_id, $t) {
                            $response = $exception->getMessage();
                            Log::error($response);
                            QueryLog::end_rest_service_query($query_log_id, '', 'error', $response);

                            $t['status'] = 'error';
                            $t['error_message'] = $response;
                            $t['gw_query_log_id'] = $gw_query_log_id;

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
