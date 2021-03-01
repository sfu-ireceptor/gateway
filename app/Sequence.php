<?php

namespace App;

use Facades\App\RestService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class Sequence
{
    // combined search taking both sample and sequence filters
    // ([sample filters] -> (sample query) => [sample id list]) + [sequence filters] -> (sequence_summary query)
    public static function full_search($sample_filters, $sequence_filters, $username)
    {
        // get filtered samples
        $sample_data = Sample::find($sample_filters, $username, false);
        $sample_list = $sample_data['items'];

        // generate list of sample ids filters
        $sample_id_filters = [];
        foreach ($sample_list as $sample) {
            $sample_id_filters['ir_project_sample_id_list_' . $sample->real_rest_service_id][] = $sample->repertoire_id;
        }

        // get sequences summary
        $sequence_filters = array_merge($sequence_filters, $sample_id_filters);
        $sequence_data = self::summary($sequence_filters, $username);

        // include repositories which failed to return samples
        $sequence_data['rs_list_no_response'] = array_merge($sequence_data['rs_list_no_response'], $sample_data['rs_list_no_response']);

        // split list of servers which didn't respond by "timeout" or "error"
        $sequence_data['rs_list_no_response_timeout'] = [];
        $sequence_data['rs_list_no_response_error'] = [];

        foreach ($sequence_data['rs_list_no_response'] as $rs) {
            if ($rs->error_type == 'timeout') {
                $sequence_data['rs_list_no_response_timeout'][] = $rs;
            } else {
                $sequence_data['rs_list_no_response_error'][] = $rs;
            }
        }

        return $sequence_data;
    }

    public static function summary($filters, $username)
    {
        // get sequences summary
        $response_list = RestService::sequences_summary($filters, $username);

        // generate stats
        $data = self::process_response($response_list);

        // get a few sequences from each service
        $response_list = RestService::sequence_list($filters);

        // merge responses
        $sequence_list = [];
        foreach ($response_list as $response) {
            $rs = $response['rs'];

            // if error, add to list of problematic repositories
            // (if not already there)
            if ($response['status'] == 'error') {
                $is_no_response = false;

                foreach ($data['rs_list_no_response'] as $rs_no_response) {
                    if ($rs_no_response->id == $rs->id) {
                        $is_no_response = true;
                    }
                }

                if ($is_no_response) {
                    continue;
                } else {
                    $data['rs_list_no_response'][] = $rs;
                }
            }

            $obj = $response['data'];
            $sequence_list = array_merge($sequence_list, data_get($obj, 'Rearrangement', []));
        }

        // convert any array properties to strings
        $sequence_list = array_map('convert_arrays_to_strings', $sequence_list);
        $sequence_list = FieldName::convertObjectList($sequence_list, 'ir_adc_api_query', 'ir_id');

        // add to stats data
        $data['items'] = $sequence_list;

        // split list of servers which didn't respond by "timeout" or "error"
        $data['rs_list_no_response_timeout'] = [];
        $data['rs_list_no_response_error'] = [];

        foreach ($data['rs_list_no_response'] as $rs) {
            if ($rs->error_type == 'timeout') {
                $data['rs_list_no_response_timeout'][] = $rs;
            } else {
                $data['rs_list_no_response_error'][] = $rs;
            }
        }

        return $data;
    }

    public static function expectedSequencesByRestSevice($filters, $username)
    {
        $response_list = RestService::sequences_summary($filters, $username, false);
        $expected_nb_sequences_by_rs = [];
        foreach ($response_list as $response) {
            $rest_service_id = $response['rs']->id;

            $nb_sequences = 0;
            if (isset($response['data'])) {
                $sample_list = $response['data'];
                foreach ($sample_list as $sample) {
                    if (isset($sample->ir_filtered_sequence_count)) {
                        $nb_sequences += $sample->ir_filtered_sequence_count;
                    }
                }
            }

            $expected_nb_sequences_by_rs[$rest_service_id] = $nb_sequences;
        }

        return $expected_nb_sequences_by_rs;
    }

    public static function sequencesTSVFolder($filters, $username, $url = '', $sample_filters = [])
    {
        // allow more time than usual for this request
        set_time_limit(config('ireceptor.gateway_file_request_timeout'));

        // do extra sequence summary request to get expected number of sequences
        // for sanity check after download
        $expected_nb_sequences_by_rs = self::expectedSequencesByRestSevice($filters, $username);

        // if total expected nb sequences is 0, immediately fail download
        $total_expected_nb_sequences = 0;
        foreach ($expected_nb_sequences_by_rs as $rs => $count) {
            $total_expected_nb_sequences += $count;
        }
        if($total_expected_nb_sequences <= 0) {
            throw new \Exception('No sequences to download');
        }

        // create receiving folder
        $storage_folder = storage_path() . '/app/public/';
        $now = time();
        $time_str = date('Y-m-d_Hi', $now);
        $folder_name = 'ir_' . $time_str . '_' . uniqid();
        $folder_path = $storage_folder . $folder_name;
        File::makeDirectory($folder_path, 0777, true, true);

        $metadata_response_list = RestService::sample_list_repertoire_data($filters, $folder_path, $username);
        $response_list = RestService::sequences_data($filters, $folder_path, $username, $expected_nb_sequences_by_rs);

        $file_stats = self::file_stats($response_list, $expected_nb_sequences_by_rs);

        // if some files are incomplete, log it
        foreach ($file_stats as $t) {
            if ($t['nb_sequences'] != $t['expected_nb_sequences']) {
                $delta = ($t['expected_nb_sequences'] - $t['nb_sequences']);
                $str = 'expected ' . $t['expected_nb_sequences'] . ' sequences, got ' . $t['nb_sequences'] . ' sequences (difference=' . $delta . ' sequences)';
                Log::warning($t['rest_service_name'] . ': ' . $str);

                $query_log_id = $t['query_log_id'];
                $ql = QueryLog::find($query_log_id);
                $ql->message .= $str;
                $ql->status = 'error';
                $ql->save();
            }
        }

        $is_download_incomplete = false;

        // did the download fail for some services?
        $failed_rs = [];
        foreach ($response_list as $response) {
            if ($response['status'] == 'error') {
                $failed_rs[] = $response['rs'];
                $is_download_incomplete = true;
            }
        }

        // did the repertoire query fail for some services?
        foreach ($metadata_response_list as $response) {
            if ($response['status'] == 'error') {
                $failed_rs[] = $response['rs'];
                $is_download_incomplete = true;
            }
        }

        // are some files incomplete?
        $nb_sequences_total = 0;
        $expected_nb_sequences_total = 0;
        foreach ($file_stats as $t) {
            $nb_sequences_total += $t['nb_sequences'];
            $expected_nb_sequences_total += $t['expected_nb_sequences'];
        }
        if ($nb_sequences_total < $expected_nb_sequences_total) {
            $is_download_incomplete = true;
        }

        // if download is incomplete, update gateway query status
        if ($is_download_incomplete) {
            $gw_query_log_id = request()->get('query_log_id');
            $error_message = 'Download is incomplete';
            QueryLog::set_gateway_query_status($gw_query_log_id, 'service_error', $error_message);
        }

        // generate info.txt
        $info_file_path = self::generate_info_file($folder_path, $url, $sample_filters, $filters, $file_stats, $username, $now, $failed_rs);

        $t = [];
        $t['folder_path'] = $folder_path;
        $t['response_list'] = $response_list;
        $t['metadata_response_list'] = $metadata_response_list;
        $t['info_file_path'] = $info_file_path;
        $t['is_download_incomplete'] = $is_download_incomplete;
        $t['file_stats'] = $file_stats;

        return $t;
    }

    public static function sequencesTSV($filters, $username, $url = '', $sample_filters = [])
    {
        $t = self::sequencesTSVFolder($filters, $username, $url, $sample_filters);

        $folder_path = $t['folder_path'];
        $response_list = $t['response_list'];
        $metadata_response_list = $t['metadata_response_list'];
        $info_file_path = $t['info_file_path'];
        $is_download_incomplete = $t['is_download_incomplete'];
        $file_stats = $t['file_stats'];

        // zip files
        $zip_path = self::zip_files($folder_path, $response_list, $metadata_response_list, $info_file_path);

        // delete files
        self::delete_files($folder_path);

        $zip_public_path = 'storage' . str_after($folder_path, storage_path('app/public')) . '.zip';

        $t = [];
        $t['size'] = filesize($zip_path);
        $t['system_path'] = $zip_path;
        $t['public_path'] = $zip_public_path;
        $t['is_download_incomplete'] = $is_download_incomplete;
        $t['file_stats'] = $file_stats;

        return $t;
    }

    public static function process_response($response_list)
    {
        // initialize return array
        $data = [];
        $data['rs_list'] = [];
        $data['rs_list_no_response'] = [];
        $data['summary'] = [];

        // process returned data
        foreach ($response_list as $response) {
            $rs = $response['rs'];

            if ($response['status'] == 'error') {
                $rs->error_type = $response['error_type'];
                $data['rs_list_no_response'][] = $rs;
            }

            $sample_list = $response['data'];
            $sample_list = Sample::convert_sample_list($sample_list, $rs);

            // create full list of samples (for graphs)
            $data['summary'] = array_merge($data['summary'], $sample_list);

            $rs_data = self::stats($sample_list);
            $rs_data['rs'] = $rs;
            $data['rs_list'][] = $rs_data;
        }

        // aggregate summary statistics
        $data = self::aggregate_stats($data);

        return $data;
    }

    public static function stats($sample_list)
    {
        $total_sequences = 0;
        $total_filtered_sequences = 0;

        $lab_list = [];
        $lab_sequence_count = [];

        $study_list = [];
        $study_sequence_count = [];

        foreach ($sample_list as $sample) {
            // sequence count for that sample
            if (isset($sample->ir_sequence_count)) {
                $total_sequences += $sample->ir_sequence_count;
            }

            // filtered sequence count for that sample
            if (isset($sample->ir_filtered_sequence_count)) {
                $nb_filtered_sequences = $sample->ir_filtered_sequence_count;
                $total_filtered_sequences += $nb_filtered_sequences;

                // add lab
                $lab_name = '';
                if (isset($sample->lab_name)) {
                    $lab_name = $sample->lab_name;
                } elseif (isset($sample->collected_by)) {
                    $lab_name = $sample->collected_by;
                }

                if ($lab_name != '') {
                    if (! in_array($lab_name, $lab_list)) {
                        $lab_list[] = $lab_name;
                        $lab_sequence_count[$lab_name] = 0;
                    }
                    $lab_sequence_count[$lab_name] += $nb_filtered_sequences;
                }

                // add study
                $study_title = isset($sample->study_title) ? $sample->study_title : '';
                if ($study_title != '') {
                    if (! in_array($study_title, $study_list)) {
                        $study_list[] = $study_title;
                        $study_sequence_count[$study_title] = 0;
                    }
                    $study_sequence_count[$study_title] += $nb_filtered_sequences;
                }
            }
        }

        $study_tree = [];
        $lab_data = [];
        $new_study_data = [];

        foreach ($sample_list as $sample) {
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
            if (! isset($sample->study_title)) {
                $sample->study_title = '';
            }
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

        $rs_data = [];

        $rs_data['total_samples'] = count($sample_list);
        $rs_data['total_labs'] = count($lab_list);
        $rs_data['total_studies'] = count($study_list);
        $rs_data['total_sequences'] = $total_sequences;
        $rs_data['total_filtered_sequences'] = $total_filtered_sequences;
        $rs_data['study_tree'] = $study_tree;

        return $rs_data;
    }

    public static function aggregate_stats($data)
    {
        $filtered_repositories = [];

        $total_filtered_repositories = 0;
        $total_filtered_labs = 0;
        $total_filtered_studies = 0;
        $total_filtered_samples = 0;
        $total_filtered_sequences = 0;

        foreach ($data['rs_list'] as $rs_data) {
            if ($rs_data['total_samples'] > 0) {
                $filtered_repositories[] = $rs_data['rs'];
                $total_filtered_repositories++;
            }

            $total_filtered_samples += $rs_data['total_samples'];
            $total_filtered_labs += $rs_data['total_labs'];
            $total_filtered_studies += $rs_data['total_studies'];
            $total_filtered_sequences += $rs_data['total_filtered_sequences'];
        }

        // sort alphabetically repositories/labs/studies
        $data['rs_list'] = Sample::sort_rest_service_list($data['rs_list']);

        $data['total_filtered_samples'] = $total_filtered_samples;
        $data['total_filtered_repositories'] = $total_filtered_repositories;
        $data['total_filtered_labs'] = $total_filtered_labs;
        $data['total_filtered_studies'] = $total_filtered_studies;
        $data['total_filtered_sequences'] = $total_filtered_sequences;
        $data['filtered_repositories'] = $filtered_repositories;

        return $data;
    }

    public static function generate_info_file($folder_path, $url, $sample_filters, $filters, $file_stats, $username, $now, $failed_rs)
    {
        $s = '';
        $s .= '* Summary *' . "\n";

        $nb_sequences_total = 0;
        $expected_nb_sequences_total = 0;
        foreach ($file_stats as $t) {
            $nb_sequences_total += $t['nb_sequences'];
            $expected_nb_sequences_total += $t['expected_nb_sequences'];
        }

        $is_download_incomplete = ($nb_sequences_total < $expected_nb_sequences_total);
        if ($is_download_incomplete) {
            $s .= 'Warning: some of the files appears to be incomplete:' . "\n";
            $s .= 'Total: ' . $nb_sequences_total . ' sequences, but ' . $expected_nb_sequences_total . ' were expected.' . "\n";
        } else {
            $s .= 'Total: ' . $nb_sequences_total . ' sequences' . "\n";
        }

        foreach ($file_stats as $t) {
            if ($is_download_incomplete && ($t['nb_sequences'] < $t['expected_nb_sequences'])) {
                $s .= $t['name'] . ' (incomplete, expected ' . $t['expected_nb_sequences'] . ' sequences): ' . $t['nb_sequences'] . ' sequences (' . $t['size'] . ')' . "\n";
            } else {
                $s .= $t['name'] . ': ' . $t['nb_sequences'] . ' sequences (' . $t['size'] . ')' . "\n";
            }
        }
        $s .= "\n";

        if (! empty($failed_rs)) {
            $s .= 'Warning: some files are missing because an error occurred while downloading sequences from these repositories:' . "\n";
            foreach ($failed_rs as $rs) {
                // code...
            }
            $s .= $rs->name . "\n";
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
        foreach (RestService::findEnabled() as $rs) {
            $sample_id_list_key = 'ir_project_sample_id_list_' . $rs->id;
            unset($filters[$sample_id_list_key]);
        }

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

        return $info_file_path;
    }

    public static function zip_files($folder_path, $response_list, $metadata_response_list, $info_file_path)
    {
        $zipPath = $folder_path . '.zip';
        Log::info('Zip files to ' . $zipPath);
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE);
        foreach ($response_list as $response) {
            if (isset($response['data']['file_path'])) {
                $file_path = $response['data']['file_path'];
                Log::debug('Adding to ZIP: ' . $file_path);
                $zip->addFile($file_path, basename($file_path));
            }
        }
        foreach ($metadata_response_list as $response) {
            if (isset($response['data']['file_path'])) {
                $file_path = $response['data']['file_path'];

                // try to prettify json
                $json_data = file_get_contents($file_path);
                $json_data_pretty = json_encode(json_decode($json_data), JSON_PRETTY_PRINT);
                if ($json_data_pretty != null) {
                    $json_data = $json_data_pretty;
                }

                file_put_contents($file_path, $json_data);

                Log::debug('Adding to ZIP: ' . $file_path);
                $zip->addFile($file_path, basename($file_path));
            }
        }
        $zip->addFile($info_file_path, basename($info_file_path));
        $zip->close();

        return $zipPath;
    }

    public static function delete_files($folder_path)
    {
        Log::debug('Deleting downloaded files...');
        if (File::exists($folder_path)) {
            Storage::deleteDirectory($folder_path);
        }
    }

    public static function file_stats($response_list, $expected_nb_sequences_by_rs)
    {
        Log::debug('Get TSV files stats');
        $file_stats = [];
        foreach ($response_list as $response) {
            $rest_service_id = $response['rs']->id;

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
                $t['nb_sequences'] = $n - 1; // don't count first line (columns headers)
                $t['expected_nb_sequences'] = 0;
                if (isset($expected_nb_sequences_by_rs[$rest_service_id])) {
                    $t['expected_nb_sequences'] = $expected_nb_sequences_by_rs[$rest_service_id];
                } else {
                    Log::error('rest_service ' . $rest_service_id . ' is missing from $expected_nb_sequences_by_rs array');
                    Log::error($expected_nb_sequences_by_rs);
                }
                $t['query_log_id'] = $response['query_log_id'];
                $t['rest_service_name'] = $response['rs']->name;
                $t['incomplete'] = ($t['nb_sequences'] != $t['expected_nb_sequences']);

                $file_stats[] = $t;
            }
        }

        return $file_stats;
    }
}
