<?php

namespace App;

use ZipArchive;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Eloquent\Model;

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

    public static function samples($filters, $username)
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
        foreach (self::findEnabled() as $rs) {
            try {
                $uri = 'samples';

                // add version prefix if not v1
                if ($rs->version > 1) {
                    $uri = 'v' . $rs->version . '/' . $uri;
                }

                // get sample data from service
                $sample_list = self::postRequest($rs, $uri, $filters);

                // convert sample data to v2 (if necessary)
                if ($rs->version != 2) {
                    $sample_list = FieldName::convertObjectList($sample_list, 'ir_v' . $rs->version, 'ir_v2');
                }
            } catch (\Exception $e) {
                $message = $e->getMessage();
                Log::error($message);
                continue;
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
        //var_dump($data['rs_list']); die();

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

    public static function sequences_summary($filters, $username)
    {
        // initialize return array
        $data = [];
        $data['items'] = [];
        $data['summary'] = [];
        $data['rs_list'] = [];

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

            try {
                $obj = self::postRequest($rs, 'v2/sequences_summary', $filters);
            } catch (\Exception $e) {
                continue;
            }
            //var_dump($obj); die();
            $data['items'] = array_merge($obj->items, $data['items']);
            $data['summary'] = array_merge($obj->summary, $data['summary']);

            $rs_data = [];
            $rs_data['rs'] = $rs;
            $rs_data['summary'] = $obj->summary;

            // calculate summary statistics
            $lab_list = [];
            $study_list = [];
            $total_sequences = 0;
            $filtered_sequences = 0;
            foreach ($obj->summary as $sample) {
                // If there are some sequence in this sample
                if (isset($sample->ir_filtered_sequence_count)) {
                    $filtered_sequences += $sample->ir_filtered_sequence_count;
                    // If we have a lab and we haven't seen it allready, add it
                    if (isset($sample->lab_name)) {
                        if (! in_array($sample->lab_name, $lab_list)) {
                            $lab_list[] = $sample->lab_name;
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
                        }
                    }
                }

                // If we have a total sequence count, add the total up.
                if (isset($sample->ir_sequence_count)) {
                    $total_sequences += $sample->ir_sequence_count;
                }
            }
            $rs_data['total_samples'] = count($obj->summary);
            $rs_data['total_labs'] = count($lab_list);
            $rs_data['total_studies'] = count($study_list);
            $rs_data['total_sequences'] = $total_sequences;
            $rs_data['filtered_sequences'] = $filtered_sequences;
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

    public static function sequencesCSV($filters, $username)
    {
        // directory
        $time_str = date('Y-m-d_G-i-s', time());
        $directory_path = $time_str . '_' . uniqid();
        $old = umask(0);
        File::makeDirectory(public_path() . '/data/' . $directory_path, 0777, true, true);
        umask($old);

        // file
        $filePath = '/data/' . $directory_path . '/data.csv';
        $systemFilePath = public_path() . $filePath;

        // add username to filters
        $filters['output'] = 'csv';
        $filters['username'] = $username;

        // get csv data and write it to file
        $csv_header_written = false;
        foreach (self::findEnabled() as $rs) {
            Log::info('RS: ' . $rs->id);

            $filters['csv_header'] = false;
            if (! $csv_header_written) {
                $filters['csv_header'] = true;
            }

            $sample_id_list_key = 'ir_project_sample_id_list_' . $rs->id;
            if (array_key_exists($sample_id_list_key, $filters) && ! empty($filters[$sample_id_list_key])) {
                // remove REST service id
                // ir_project_sample_id_list_2 -> ir_project_sample_id_list
                unset($filters['ir_project_sample_id_list']);
                $filters['ir_project_sample_id_list'] = $filters[$sample_id_list_key];
            } else {
                // if no sample id for this REST service, don't query it.
                continue;
            }

            Log::info('doing req to RS with params:');
            Log::info($filters);

            try {
                self::postRequest($rs, 'sequences', $filters, $systemFilePath);
            } catch (\Exception $e) {
                $message = $e->getMessage();
                Log::error($message);
                continue;
            }

            $csv_header_written = true;
        }

        // zip file
        $zipSystemFilePath = $systemFilePath . '.zip';
        $zip = new ZipArchive();
        $zip->open($zipSystemFilePath, ZipArchive::CREATE);
        $zip->addFile($systemFilePath, 'data.csv');
        $zip->close();
        $zipFilePath = $filePath . '.zip';

        // delete original file
        File::delete($systemFilePath);

        Log::info('$zipFilePath=' . $zipFilePath);

        return $zipFilePath;
    }

    public static function search($sample_filters, $sequence_filters, $username)
    {
        // get samples
        $sample_data = self::samples($sample_filters, $username);
        $sample_list = $sample_data['items'];

        // get samples ids
        $sample_id_filters = [];
        foreach ($sample_list as $sample) {
            $sample_id_filters['ir_project_sample_id_list_' . $sample->rest_service_id][] = $sample->ir_project_sample_id;
        }

        // get sequences summary
        $sequence_filters = array_merge($sequence_filters, $sample_id_filters);
        $sequence_data = self::sequences_summary($sequence_filters, $username);

        return $sequence_data;
    }

    public static function postRequest($rs, $path, $params, $filePath = '', $returnArray = false)
    {
        $defaults = [];
        $defaults['base_uri'] = $rs->url;
        $defaults['verify'] = false;    // accept self-signed SSL certificates
        //var_dump($defaults); die();

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
        if (str_contains($rs->url, 'vdj')) {
            // build query string with special function which doesn't add brackets
            $queryString = \GuzzleHttp\Psr7\build_query($params, PHP_QUERY_RFC1738);

            // set request body and header manually
            $options['body'] = $queryString;
            $options['headers'] = ['Content-Type' => 'application/x-www-form-urlencoded'];
        } else {
            // if PHP service, just let Guzzle add brackets for array params
            $options['form_params'] = $params;
        }

        if ($filePath != '') {
            $dirPath = dirname($filePath);
            if (! is_dir($dirPath)) {
                Log::info('Creating directory ' . $dirPath);
                mkdir($dirPath, 0755, true);
            }

            $options['sink'] = fopen($filePath, 'a');
            Log::info('Guzzle: saving to ' . $filePath);
        }

        // execute request
        try {
            Log::info('Start node query ' . $rs->url . $path . ' with POST params:');
            Log::info($params);

            $response = $client->request('POST', $path, $options);
            Log::info('End query - success');
        } catch (\ClientException $exception) {
            //var_dump($exception); die();
            $response = $exception->getResponse()->getBody()->getContents();
            Log::error($response);

            return [];
        } catch (\Exception $exception) {
            //var_dump($exception); die();
            $response = $exception->getMessage();
            Log::error($response);

            return [];
        }

        if ($filePath == '') {
            // return object generated from json response
            //var_dump($response->getBody()); die();
            $json = $response->getBody();
            //var_dump($json); die();

            $obj = json_decode($json, $returnArray);
            //var_dump($obj); die();

            return $obj;
        }
    }
}
