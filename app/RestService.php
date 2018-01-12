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

    public static function findEnabled($fieldList = null)
    {
        $l = static::where('enabled', '=', true)->orderBy('name', 'asc')->get($fieldList);

        return $l;
    }

    public static function metadata($username)
    {
        return Sample::metadata();
    }

    public static function samples($filters, $username, $query_log_id)
    {
        // initialize return array
        $data = [];
        $data['items'] = [];
        $data['rs_list'] = [];
        $data['total'] = 0;

        // Initialize the set of filters being used
        $data['filter_fields'] = [];
        // For each filter that is active, keep track of the filter field so
        // UI can display the filters that are active.
        foreach ($filters as $filter_key => $filter_value) {
            // Filters are sometimes given to the API without values, so we
            // have to detect this and only display if there are values.
            if (count($filter_value) > 0) {
                // Some parameters can be arrays, handle this and conver the array
                // to a string representation of the filter.
                if (is_array($filter_value)) {
                    $data['filter_fields'][$filter_key] = implode(', ', $filter_value);
                } else {
                    $data['filter_fields'][$filter_key] = $filter_value;
                }
            }
        }

        $filters['username'] = $username;
        $filters['ir_username'] = $username;

        // get samples from each service
        $rs_promise_list = [];
        foreach (self::findEnabled() as $rs) {
            $uri = 'samples';

            // add version prefix if not v1
            if ($rs->version > 1) {
                $uri = 'v' . $rs->version . '/' . $uri;
            }

            // start samples request to service
            $t = [];
            $t['rs'] = $rs;
            $t['promise'] = self::postRequest($rs, $uri, $filters, $query_log_id);
            $rs_promise_list[] = $t;
        }

        // wait for all requests to finish
        $response_list = [];
        foreach ($rs_promise_list as $t) {
            $t['response'] = $t['promise']->wait();
            $response_list[] = $t;
        }

        // process returned data
        foreach ($response_list as $t) {
            $rs = $t['rs'];
            $response = $t['response'];

            $sample_list = $response['data'];

            // convert sample data to v2 (if necessary)
            if ($rs->version != 2) {
                $sample_list = FieldName::convertObjectList($sample_list, 'ir_v' . $rs->version, 'ir_v2');
            }

            foreach ($sample_list as $sample) {
                $sample->rest_service_id = $rs->id;
                $sample->rest_service_name = $rs->name;

                if (isset($sample->study_id)) {
                    if (preg_match('/PRJ/', $sample->study_id)) {
                        $sample->study_url = 'https://www.ncbi.nlm.nih.gov/bioproject/?term=' . $sample->study_id;
                    } elseif (preg_match('/SRP/', $sample->study_id)) {
                        $sample->study_url = 'https://www.ncbi.nlm.nih.gov/Traces/sra/?study=' . $sample->study_id;
                    } else {
                        unset($sample->study_url);
                    }
                }
            }

            // calculate summary statistics
            $lab_list = [];
            $lab_sequence_count = [];
            $study_sequence_count = [];
            $study_list = [];
            $total_sequences = 0;
            foreach ($sample_list as $sample) {
                if (isset($sample->ir_sequence_count)) {
                    $sequence_count = $sample->ir_sequence_count;
                } else {
                    $sequence_count = 0;
                }

                if (isset($sample->lab_name)) {
                    if (! in_array($sample->lab_name, $lab_list)) {
                        $lab_list[] = $sample->lab_name;
                        $lab_sequence_count[$sample->lab_name] = $sequence_count;
                    } else {
                        $lab_sequence_count[$sample->lab_name] += $sequence_count;
                    }
                } elseif (isset($sample->collected_by)) {
                    if (! in_array($sample->collected_by, $lab_list)) {
                        $lab_list[] = $sample->collected_by;
                    }
                }

                if (! in_array($sample->study_title, $study_list)) {
                    $study_list[] = $sample->study_title;
                    $study_sequence_count[$sample->study_title] = $sequence_count;
                } else {
                    $study_sequence_count[$sample->study_title] += $sequence_count;
                }

                $total_sequences += $sequence_count;
            }

            $study_tree = [];
            foreach ($sample_list as $sample) {
                // Handle the case where a sample doesn't have a lab_name.
                if (isset($sample->lab_name)) {
                    $lab = $sample->lab_name;
                } else {
                    $lab = 'UNKNOWN';
                }

                // If we don't have this lab already, create it.
                if (! isset($study_tree[$lab])) {
                    $lab_data['name'] = $lab;
                    if (isset($lab_sequence_count[$lab])) {
                        $lab_data['total_sequences'] = $lab_sequence_count[$lab];
                    } else {
                        $lab_data['total_sequences'] = 0;
                    }
                    $study_tree[$lab] = $lab_data;
                }

                // Check to see if the study exists in the lab, and if not, create it.
                if (! isset($study_tree[$lab]['studies'])) {
                    $new_study_data['study_title'] = $sample->study_title;
                    if (isset($study_sequence_count[$sample->study_title])) {
                        $new_study_data['total_sequences'] = $study_sequence_count[$sample->study_title];
                    } else {
                        $new_study_data['total_sequences'] = 0;
                    }
                    $study_tree[$lab]['studies'][$sample->study_title] = $new_study_data;
                } else {
                    if (! in_array($sample->study_title, $study_tree[$lab]['studies'])) {
                        $new_study_data['study_title'] = $sample->study_title;
                        if (isset($sample->study_url)) {
                            $new_study_data['study_url'] = $sample->study_url;
                        } else {
                            unset($new_study_data['study_url']);
                        }
                        if (isset($study_sequence_count[$sample->study_title])) {
                            $new_study_data['total_sequences'] = $study_sequence_count[$sample->study_title];
                        } else {
                            $new_study_data['total_sequences'] = 0;
                        }
                        $study_tree[$lab]['studies'][$sample->study_title] = $new_study_data;
                    }
                }
            }

            // rest service data
            $rs_data = [];
            $rs_data['rs'] = $rs;
            $rs_data['study_tree'] = $study_tree;
            $rs_data['total_samples'] = count($sample_list);
            $rs_data['total_labs'] = count($lab_list);
            $rs_data['total_studies'] = count($study_list);
            $rs_data['total_sequences'] = $total_sequences;
            $data['rs_list'][] = $rs_data;

            // sample data
            $data['total'] += $rs_data['total_samples'];
            $data['items'] = array_merge($sample_list, $data['items']);
        }

        // aggregate summary statistics
        $total_filtered_repositories = 0;
        $total_filtered_labs = 0;
        $total_filtered_studies = 0;
        $total_filtered_samples = 0;
        $total_filtered_sequences = 0;
        $filtered_repositories = [];

        foreach ($data['rs_list'] as $rs_data) {
            if ($rs_data['total_samples'] > 0) {
                $total_filtered_repositories++;
                $filtered_repositories[] = $rs_data['rs'];
            }

            $total_filtered_samples += $rs_data['total_samples'];
            $total_filtered_labs += $rs_data['total_labs'];
            $total_filtered_studies += $rs_data['total_studies'];
            $total_filtered_sequences += $rs_data['total_sequences'];
        }

        $data['total_filtered_samples'] = $total_filtered_samples;
        $data['total_filtered_repositories'] = $total_filtered_repositories;
        $data['total_filtered_labs'] = $total_filtered_labs;
        $data['total_filtered_studies'] = $total_filtered_studies;
        $data['total_filtered_sequences'] = $total_filtered_sequences;
        $data['filtered_repositories'] = $filtered_repositories;

        return $data;
    }

    public static function sequences_summary($filters, $username, $query_log_id)
    {
        // initialize return array
        $data = [];
        $data['items'] = [];
        $data['summary'] = [];
        $data['rs_list'] = [];
        $data['rs_list_no_response'] = [];

        // remove gateway-specific filters
        unset($filters['cols']);
        unset($filters['filters_order']);

        // Trakc the list of filters being used
        $data['filter_fields'] = [];
        // For each filter that is active, keep track of the filter field so
        // UI can display the filters that are active.
        foreach ($filters as $filter_key => $filter_value) {
            // Filters are sometimes given to the API without values, so we
            // have to detect this and only display if there are values.
            if (count($filter_value) > 0) {
                // Some parameters can be arrays, handle this and conver the array
                // to a string representation of the filter.
                if (is_array($filter_value)) {
                    // We want to ignore the filters around sample in this list (we don't want to display them)
                    if (strpos($filter_key, 'ir_project_sample_id_list_') !== false) {
                        $data['filtered_fields'][$filter_key] = implode(', ', $filter_value);
                    }
                } else {
                    $data['filter_fields'][$filter_key] = $filter_value;
                }
            }
        }

        // add username to filters
        $filters['username'] = $username;
        $filters['ir_username'] = $username;

        $rs_promise_list = [];
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

            // start sequence request to service
            $t = [];
            $t['rs'] = $rs;
            $t['promise'] = self::postRequest($rs, 'v2/sequences_summary', $filters, $query_log_id);
            $rs_promise_list[] = $t;
        }

        // wait for all requests to finish
        $response_list = [];
        foreach ($rs_promise_list as $t) {
            $t['response'] = $t['promise']->wait();
            $response_list[] = $t;
        }

        // process returned data
        foreach ($response_list as $t) {
            $rs = $t['rs'];
            $response = $t['response'];

            $obj = $response['data'];

            // check response format
            if ($response['status'] == 'error') {
                $data['rs_list_no_response'][] = $rs;
                continue;
            } elseif (! isset($obj->items)) {
                Log::error('No "items" element in JSON response:');
                Log::error($obj);
                $data['rs_list_no_response'][] = $rs;
                continue;
            } elseif (! isset($obj->summary)) {
                Log::error('No "summary" element in JSON response.');
                Log::error($obj);
                $data['rs_list_no_response'][] = $rs;
                continue;
            }

            $data['items'] = array_merge($obj->items, $data['items']);
            $data['summary'] = array_merge($obj->summary, $data['summary']);

            $rs_data = [];
            $rs_data['rs'] = $rs;
            $rs_data['summary'] = $obj->summary;

            // calculate summary statistics
            $lab_list = [];
            $lab_sequence_count = [];

            $study_list = [];
            $study_sequence_count = [];

            $total_sequences = 0;
            $filtered_sequences = 0;
            foreach ($obj->summary as $sample) {
                if (isset($sample->study_id)) {
                    if (preg_match('/PRJ/', $sample->study_id)) {
                        $sample->study_url = 'https://www.ncbi.nlm.nih.gov/bioproject/?term=' . $sample->study_id;
                    } elseif (preg_match('/SRP/', $sample->study_id)) {
                        $sample->study_url = 'https://www.ncbi.nlm.nih.gov/Traces/sra/?study=' . $sample->study_id;
                    } else {
                        unset($sample->study_url);
                    }
                }
                // If there are some sequence in this sample
                if (isset($sample->ir_filtered_sequence_count)) {
                    $filtered_sequences += $sample->ir_filtered_sequence_count;
                    // If we have a lab and we haven't seen it allready, add it
                    if (isset($sample->lab_name)) {
                        if (! in_array($sample->lab_name, $lab_list)) {
                            $lab_list[] = $sample->lab_name;
                            $lab_sequence_count[$sample->lab_name] = $sample->ir_filtered_sequence_count;
                        } else {
                            $lab_sequence_count[$sample->lab_name] += $sample->ir_filtered_sequence_count;
                        }
                    } elseif (isset($sample->collected_by)) {
                        if (! in_array($sample->collected_by, $lab_list)) {
                            $lab_list[] = $sample->collected_by;
                        }
                    }
                    // If we have a study title and we haven't seen it allready, add it
                    if (isset($sample->study_title)) {
                        if (! in_array($sample->study_title, $study_list)) {
                            $study_list[] = $sample->study_title;
                            $study_sequence_count[$sample->study_title] = $sample->ir_filtered_sequence_count;
                        } else {
                            $study_sequence_count[$sample->study_title] += $sample->ir_filtered_sequence_count;
                        }
                    }
                }

                // If we have a total sequence count, add the total up.
                if (isset($sample->ir_sequence_count)) {
                    $total_sequences += $sample->ir_sequence_count;
                }
            }

            $study_tree = [];
            foreach ($obj->summary as $sample) {
                // Handle the case where a sample doesn't have a lab_name.
                if (isset($sample->lab_name)) {
                    $lab = $sample->lab_name;
                } else {
                    $lab = 'UNKNOWN';
                }

                // If we don't have this lab already, create it.
                if (! isset($study_tree[$lab])) {
                    $lab_data['name'] = $lab;
                    if (isset($lab_sequence_count[$lab])) {
                        $lab_data['total_sequences'] = $lab_sequence_count[$lab];
                    } else {
                        $lab_data['total_sequences'] = 0;
                    }
                    $study_tree[$lab] = $lab_data;
                }

                // Check to see if the study exists in the lab, and if not, create it.
                if (! isset($study_tree[$lab]['studies'])) {
                    $new_study_data['study_title'] = $sample->study_title;
                    if (isset($study_sequence_count[$sample->study_title])) {
                        $new_study_data['total_sequences'] = $study_sequence_count[$sample->study_title];
                    } else {
                        $new_study_data['total_sequences'] = 0;
                    }
                    $study_tree[$lab]['studies'][$sample->study_title] = $new_study_data;
                } else {
                    if (! in_array($sample->study_title, $study_tree[$lab]['studies'])) {
                        $new_study_data['study_title'] = $sample->study_title;
                        if (isset($sample->study_url)) {
                            $new_study_data['study_url'] = $sample->study_url;
                        } else {
                            unset($new_study_data['study_url']);
                        }
                        if (isset($study_sequence_count[$sample->study_title])) {
                            $new_study_data['total_sequences'] = $study_sequence_count[$sample->study_title];
                        } else {
                            $new_study_data['total_sequences'] = 0;
                        }
                        $study_tree[$lab]['studies'][$sample->study_title] = $new_study_data;
                    }
                }
            }
            $rs_data['total_samples'] = count($obj->summary);
            $rs_data['total_labs'] = count($lab_list);
            $rs_data['total_studies'] = count($study_list);
            $rs_data['total_sequences'] = $total_sequences;
            $rs_data['filtered_sequences'] = $filtered_sequences;
            $rs_data['study_tree'] = $study_tree;

            $data['rs_list'][] = $rs_data;
        }

        // aggregate summary statistics
        $total_filtered_repositories = 0;
        $total_filtered_labs = 0;
        $total_filtered_studies = 0;
        $total_filtered_samples = 0;
        $total_filtered_sequences = 0;
        $filtered_repositories = [];

        foreach ($data['rs_list'] as $rs_data) {
            if ($rs_data['total_samples'] > 0) {
                $total_filtered_repositories++;
                $filtered_repositories[] = $rs_data['rs'];
            }

            $total_filtered_samples += $rs_data['total_samples'];
            $total_filtered_labs += $rs_data['total_labs'];
            $total_filtered_studies += $rs_data['total_studies'];
            $total_filtered_sequences += $rs_data['filtered_sequences'];
        }

        $data['total_filtered_samples'] = $total_filtered_samples;
        $data['total_filtered_repositories'] = $total_filtered_repositories;
        $data['total_filtered_labs'] = $total_filtered_labs;
        $data['total_filtered_studies'] = $total_filtered_studies;
        $data['total_filtered_sequences'] = $total_filtered_sequences;
        $data['filtered_repositories'] = $filtered_repositories;

        return $data;
    }

    public static function sequencesCSV($filters, $username, $query_log_id)
    {
        // allow more time than usual for this request
        set_time_limit(config('ireceptor.gateway_file_request_timeout'));

        // create receiving folder
        $storage_folder = storage_path() . '/app/public/';
        $time_str = date('Y-m-d_Hi', time());
        $folder_name = 'ir_' . $time_str . '_' . uniqid();
        $folder_path = $storage_folder . $folder_name;
        File::makeDirectory($folder_path, 0777, true, true);

        // add username to filters
        $filters['output'] = 'csv';
        $filters['username'] = $username;
        $filters['ir_username'] = $username;

        // get csv data and write it to file
        $rs_promise_list = [];
        foreach (self::findEnabled() as $rs) {
            $sample_id_list_key = 'ir_project_sample_id_list_' . $rs->id;
            if (array_key_exists($sample_id_list_key, $filters) && ! empty($filters[$sample_id_list_key])) {
                // remove REST service id
                // ir_project_sample_id_list_2 -> ir_project_sample_id_list
                unset($filters['ir_project_sample_id_list']);
                $filters['ir_project_sample_id_list'] = $filters[$sample_id_list_key];
                unset($filters[$sample_id_list_key]);
            } else {
                // if no sample id for this REST service, don't query it.
                continue;
            }

            $file_path = $folder_path . '/' . $rs->id . '.csv';

            // start request to service
            $t = [];
            $t['rs'] = $rs;
            $t['promise'] = self::postRequest($rs, 'sequences', $filters, $query_log_id, $file_path);
            $rs_promise_list[] = $t;
        }

        // wait for all requests to finish
        $response_list = [];
        foreach ($rs_promise_list as $t) {
            $t['response'] = $t['promise']->wait();
            $response_list[] = $t;
        }

        // zip CSV files
        $zipPath = $folder_path . '.zip';
        Log::info('zipping to ' . $zipPath);
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE);
        foreach ($response_list as $t) {
            $file_path = $t['response']['file_path'];
            $zip->addFile($file_path, basename($file_path));
        }

        $zip->close();

        // delete CSV files
        foreach ($response_list as $t) {
            $file_path = $t['response']['file_path'];
            File::delete($file_path);
        }

        // delete folder
        rmdir($folder_path);

        $zipPublicPath = 'storage' . str_after($folder_path, storage_path('app/public')) . '.zip';

        $t = [];
        $t['size'] = filesize($zipPath);
        $t['system_path'] = $zipPath;
        $t['public_path'] = $zipPublicPath;
        return $t;
    }

    public static function search($sample_filters, $sequence_filters, $username, $query_log_id)
    {
        // get samples
        $sample_data = self::samples($sample_filters, $username, $query_log_id);
        $sample_list = $sample_data['items'];

        // get samples ids
        $sample_id_filters = [];
        foreach ($sample_list as $sample) {
            $sample_id_filters['ir_project_sample_id_list_' . $sample->rest_service_id][] = $sample->ir_project_sample_id;
        }

        // get sequences summary
        $sequence_filters = array_merge($sequence_filters, $sample_id_filters);
        $sequence_data = self::sequences_summary($sequence_filters, $username, $query_log_id);

        return $sequence_data;
    }

    // send POST request to a given service
    public static function postRequest($rs, $path, $params, $gw_query_log_id, $file_path = '', $returnArray = false)
    {
        $defaults = [];
        $defaults['base_uri'] = $rs->url;
        $defaults['verify'] = false;    // accept self-signed SSL certificates
        if ($file_path == '') {
            $defaults['timeout'] = config('ireceptor.service_request_timeout');
        } else {
            $defaults['timeout'] = config('ireceptor.service_file_request_timeout');
        }

        $client = new \GuzzleHttp\Client($defaults);

        // build request
        $options = [];
        $options['auth'] = [$rs->username, $rs->password];

        // remove null values.
        foreach ($params as $k => $v) {
            if ($v === null) {
                unset($params[$k]);
            }
        }

        // VDJServer needs array params without brackets
        if (str_contains($rs->url, 'vdj') || str_contains($rs->url, '206.12.99.176:8080')) {
            // build query string with special function which doesn't add brackets
            $queryString = \GuzzleHttp\Psr7\build_query($params, PHP_QUERY_RFC1738);

            // set request body and header manually
            $options['body'] = $queryString;
            $options['headers'] = ['Content-Type' => 'application/x-www-form-urlencoded'];
        } else {
            // if PHP service, just let Guzzle add brackets for array params
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
        $t['status'] = 'success';
        $t['data'] = [];

        // execute request
        Log::info('Start node query: ' . $rs->url . $path . '. with POST params:');
        Log::info($params);
        $query_log_id = QueryLog::start_rest_service_query($gw_query_log_id, $rs, $path, $params, $file_path);

        $promise = $client->requestAsync('POST', $path, $options)->then(
                        function (ResponseInterface $response) use ($query_log_id, $file_path, $returnArray, $t) {
                            QueryLog::end_rest_service_query($query_log_id);

                            if ($file_path == '') {
                                // return object generated from json response
                                $json = $response->getBody();
                                $obj = json_decode($json, $returnArray);
                                $t['data'] = $obj;

                                return $t;
                            } else {
                                $t['file_path'] = $file_path;

                                return $t;
                            }
                        },
                        function ($exception) use ($query_log_id, $t) {
                            $response = $exception->getMessage();
                            Log::error($response);
                            QueryLog::end_rest_service_query($query_log_id, 'error', $response);

                            $t['status'] = 'error';
                            $t['error_message'] = $response;

                            return $t;
                        }
                    );

        return $promise;
    }
}
