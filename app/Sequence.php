<?php

namespace App;

use ZipArchive;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Sequence
{
    public static function summary($filters, $username, $gw_query_log_id = null)
    {
	    $filters = self::clean_filters($filters);

        // remove gateway-specific filters
        unset($filters['cols']);
        unset($filters['filters_order']);
        unset($filters['sample_query_id']);
        unset($filters['open_filter_panel_list']);

        // add required service filters
        $filters['username'] = $username;
        $filters['ir_username'] = $username;

        // do requests
        $response_list = RestService::sequences_summary($filters);

    	// initialize return array
        $data = [];
        $data['items'] = [];
        $data['summary'] = [];
        $data['rs_list'] = [];
        $data['rs_list_no_response'] = [];

        // process returned data
        foreach ($response_list as $response) {
            $rs = $response['rs'];
            $obj = $response['data'];

            // check response format
            if ($response['status'] == 'error') {
                $data['rs_list_no_response'][] = $rs;
                QueryLog::set_gateway_query_status($gw_query_log_id, 'service_error', $response['error_message']);
                continue;
            } elseif (! isset($obj->items)) {
                $errror_message = 'No "items" element in JSON response';
                Log::error($errror_message);
                Log::error($obj);
                QueryLog::set_gateway_query_status($gw_query_log_id, 'service_error', $errror_message);
                $data['rs_list_no_response'][] = $rs;
                continue;
            } elseif (! isset($obj->summary)) {
                $errror_message = 'No "summary" element in JSON response';
                Log::error($errror_message);
                Log::error($obj);
                QueryLog::set_gateway_query_status($gw_query_log_id, 'service_error', $errror_message);
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
                // generate study URL
                if (isset($sample->study_id)) {
                    if (preg_match('/PRJ/', $sample->study_id)) {
                        $sample->study_url = 'https://www.ncbi.nlm.nih.gov/bioproject/?term=' . $sample->study_id;
                    } elseif (preg_match('/SRP/', $sample->study_id)) {
                        $sample->study_url = 'https://www.ncbi.nlm.nih.gov/Traces/sra/?study=' . $sample->study_id;
                    } else {
                        unset($sample->study_url);
                    }
                }
                // If there are some sequences in this sample
                if (isset($sample->ir_filtered_sequence_count)) {
                    $filtered_sequences += $sample->ir_filtered_sequence_count;
                    // If we have a lab and we haven't seen it already, add it
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
                    $total_sequences += $sample->ir_filtered_sequence_count;
                }
            }

            $study_tree = [];
            foreach ($obj->summary as $sample) {
                // if a sample doesn't have a lab_name.
                if (isset($sample->lab_name)) {
                    $lab = $sample->lab_name;
                } else {
                    $lab = '';
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

    public static function stats($response_list)
    {

    }

    public static function sequencesTSV($filters, $username, $gw_query_log_id = null, $url, $sample_filters = [])
    {
    	// allow more time than usual for this request
        set_time_limit(config('ireceptor.gateway_file_request_timeout'));

        $filters = self::clean_filters($filters);

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

    	$response_list = RestService::sequencesTSV($filters, $username, $gw_query_log_id, $url, $folder_path, $sample_filters);

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

    public static function clean_filters($filters)
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
}
