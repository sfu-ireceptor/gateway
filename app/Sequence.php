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
        $response_list_sequences_summary = RestService::sequences_summary($filters, $username, 'sequence');

        // generate stats
        $data = self::process_response($response_list_sequences_summary);

        // get a few sequences from each service
        $response_list = RestService::sequence_list($filters, $response_list_sequences_summary, 10, 'sequence');

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
            $rs_sequence_list = data_get($obj, 'Rearrangement', []);

            // convert any array properties to strings
            $rs_sequence_list = array_map('convert_arrays_to_strings', $rs_sequence_list);

            // convert fields
            $rs_sequence_list = FieldName::convertObjectList($rs_sequence_list, 'ir_adc_api_response', 'ir_id', 'Rearrangement', $rs->api_version);

            $sequence_list = array_merge($sequence_list, $rs_sequence_list);
        }

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

    public static function expectedSequencesByRestSevice($response_list)
    {
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

    public static function filteredSamplesByRestService($response_list)
    {
        $filtered_samples_by_rs = [];
        foreach ($response_list as $response) {
            $rest_service_id = $response['rs']->id;

            $sample_id_list = [];
            if (isset($response['data'])) {
                $sample_list = $response['data'];
                foreach ($sample_list as $sample) {
                    if (isset($sample->ir_filtered_sequence_count) && ($sample->ir_filtered_sequence_count > 0)) {
                        $sample_id_list[] = $sample->repertoire_id;
                    }
                }
            }

            $filtered_samples_by_rs[$rest_service_id] = $sample_id_list;
        }

        return $filtered_samples_by_rs;
    }

    public static function sequencesTSVFolder($filters, $username, $url = '', $sample_filters = [])
    {
        // allow more time than usual for this request
        set_time_limit(config('ireceptor.gateway_file_request_timeout'));
        Log::debug('Sequence::sequencesTSVFolder: filters = ' . json_encode($filters));
        Log::debug('Sequence::sequencesTSVFolder: sample filters = ' . json_encode($sample_filters));

        // do extra sequence summary request
        $response_list = RestService::sequences_summary($filters, $username, false, 'sequence');

        // get expected number of sequences for sanity check after download
        $expected_nb_sequences_by_rs = self::expectedSequencesByRestSevice($response_list);

        // get filtered list of repertoires ids
        $filtered_samples_by_rs = self::filteredSamplesByRestService($response_list);

        // if total expected nb sequences is 0, immediately fail download
        $total_expected_nb_sequences = 0;
        foreach ($expected_nb_sequences_by_rs as $rs => $count) {
            $total_expected_nb_sequences += $count;
        }
        if ($total_expected_nb_sequences <= 0) {
            throw new \Exception('No sequences to download');
        }

        // if total expected nb sequences > download limit, immediately fail download
        $sequences_download_limit = config('ireceptor.sequences_download_limit');
        if ($total_expected_nb_sequences > $sequences_download_limit) {
            throw new \Exception('Trying to download to many sequences: ' . $total_expected_nb_sequences . ' > ' . $sequences_download_limit);
        }

        // Full path of receiving folder for the download data, based on the 
	// download_data_folder config variable, which is relative to the
	// Laravel storage_path().
        $storage_folder = storage_path().'/'.config('ireceptor.downloads_data_folder').'/';

	// Create a unique ID for the data directory.
        $now = time();
        $time_str = date('Y-m-d_Hi', $now);
        $base_name = 'ir_' . $time_str . '_' . uniqid();
        $folder_path = $storage_folder . $base_name;

	// Create the directory
        Log::debug('Sequence::sequencesTSVFolder - Creating directory: ' . $folder_path);
        $old = umask(0);
        mkdir($folder_path, 0770);
        umask($old);

        $metadata_response_list = RestService::sample_list_repertoire_data($filtered_samples_by_rs, $folder_path, $username);
        $response_list = RestService::sequences_data($filters, $folder_path, $username, $expected_nb_sequences_by_rs);

        // Get a list of file information as a block of data.
        $file_stats = self::file_stats($response_list, $metadata_response_list, $expected_nb_sequences_by_rs);

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

        // if download is incomplete
        $download_incomplete_info = '';
        if ($is_download_incomplete) {
            // update gateway query status
            $gw_query_log_id = request()->get('query_log_id');
            $error_message = 'Download is incomplete';
            QueryLog::set_gateway_query_status($gw_query_log_id, 'service_error', $error_message);

            // generate info message
            $download_incomplete_info = '';
            if (! empty($failed_rs)) {
                // list failed repositories
                $rs_name_list = [];
                foreach ($failed_rs as $rs) {
                    $rs_name_list[] = $rs->name;
                }
                $rs_name_list_str = implode(',', $rs_name_list);

                if (count($failed_rs) == 1) {
                    $download_incomplete_info .= 'An error occured when trying to download from the repository ' . $rs_name_list_str . ".\n\n";
                } else {
                    $download_incomplete_info .= 'An error occured when trying to download from the repositories ' . $rs_name_list_str . ".\n\n";
                }

                // list successful repositories
                $success_rs = [];
                foreach ($response_list as $response) {
                    $rs = $response['rs'];
                    $is_failed = false;
                    foreach ($failed_rs as $rs_failed) {
                        if ($rs->id == $rs_failed->id) {
                            $is_failed = true;
                            break;
                        }
                    }
                    if (! $is_failed) {
                        $success_rs[] = $rs;
                    }
                }

                $rs_name_list = [];
                foreach ($success_rs as $rs) {
                    $rs_name_list[] = $rs->name;
                }
                $rs_name_list_str = implode(',', $rs_name_list);

                if (count($success_rs) == 1) {
                    $download_incomplete_info .= 'The download from the repository ' . $rs_name_list_str . " finished successfully.\n";
                } elseif (count($success_rs) > 1) {
                    $download_incomplete_info .= 'Downloads from the following repositories finished successfully: ' . $rs_name_list_str . ".\n";
                }
            } else {
                $download_incomplete_info .= 'Some files appear to be incomplete. See the included Info file for more details.';
            }
        }

        // generate info file
        $info_file_path = self::generate_info_file($folder_path, $url, $sample_filters, $filters, $file_stats, $username, $now, $failed_rs);

        // generate manifest.json
        $manifest_file_path = self::generate_manifest_file($folder_path, $url, $sample_filters, $filters, $file_stats, $username, $now, $failed_rs);

        // Generate the repertoire query file that got us to the sequence page.
        $query_sep = 'query_id=';
        // Get the query_id, it should be everything after the query_sep string.
        $seq_query_id = substr($url, strpos($url, $query_sep) + strlen($query_sep));
        // Get the query parameters for both the sequence and sample queries. The
        // sequence query parameters contains the query id of the sample query used
        // to get to the seqeunces page. The parameters are arrays of keys of the form
        // ["field":"value",...]
        $seq_query_params = Query::getParams($seq_query_id);
        $sam_query_params = [];
        if (array_key_exists('sample_query_id', $seq_query_params)) {
            $sam_query_id = $seq_query_params['sample_query_id'];
            $sam_query_params = Query::getParams($sam_query_id);
        }
        // Generate the repertoire query file based on repertoire query parameters.
        $repertoire_query_file_path = self::generate_repertoire_query_file($folder_path, $sam_query_params);
        // Generate the rearrangement query file that got us to the sequence page.
        $rearrangement_query_file_path = self::generate_rearrangement_query_file($folder_path, $filters);

        $t = [];
        $t['base_path'] = $storage_folder;
        $t['base_name'] = $base_name;
        $t['folder_path'] = $folder_path;
        $t['response_list'] = $response_list;
        $t['metadata_response_list'] = $metadata_response_list;
        $t['info_file_path'] = $info_file_path;
        $t['manifest_file_path'] = $manifest_file_path;
        $t['repertoire_query_file_path'] = $repertoire_query_file_path;
        $t['rearrangement_query_file_path'] = $rearrangement_query_file_path;
        $t['is_download_incomplete'] = $is_download_incomplete;
        $t['download_incomplete_info'] = $download_incomplete_info;
        $t['file_stats'] = $file_stats;

        return $t;
    }

    public static function sequencesTSV($filters, $username, $url = '', $sample_filters = [])
    {
        $t = self::sequencesTSVFolder($filters, $username, $url, $sample_filters);

        $base_path = $t['base_path'];
        $base_name = $t['base_name'];
        $folder_path = $t['folder_path'];
        $response_list = $t['response_list'];
        $metadata_response_list = $t['metadata_response_list'];
        $info_file_path = $t['info_file_path'];
        $manifest_file_path = $t['manifest_file_path'];
        $repertoire_query_file_path = $t['repertoire_query_file_path'];
        $rearrangement_query_file_path = $t['rearrangement_query_file_path'];
        $is_download_incomplete = $t['is_download_incomplete'];
        $download_incomplete_info = $t['download_incomplete_info'];
        $file_stats = $t['file_stats'];

        // zip files
        $zip_path = self::zip_files($folder_path, $response_list, $metadata_response_list, $info_file_path, $manifest_file_path, $repertoire_query_file_path, $rearrangement_query_file_path);

        // delete files
        self::delete_files($folder_path);

        $t = [];
        $t['size'] = filesize($zip_path);
        $t['base_path'] = $base_path;
        $t['base_name'] = $base_name;
        $t['zip_name'] = $base_name . '.zip';
        $t['system_path'] = $zip_path;
        $t['is_download_incomplete'] = $is_download_incomplete;
        $t['download_incomplete_info'] = $download_incomplete_info;
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
        $total_object_count = 0;
        $total_filtered_objects = 0;

        $lab_list = [];
        $lab_object_count = [];

        $study_list = [];
        $study_object_count = [];

        foreach ($sample_list as $sample) {
            // sequence count for that sample
            if (isset($sample->ir_sequence_count)) {
                $total_object_count += $sample->ir_sequence_count;
            }

            // filtered sequence count for that sample
            if (isset($sample->ir_filtered_sequence_count)) {
                $nb_filtered_objects = $sample->ir_filtered_sequence_count;
                $total_filtered_objects += $nb_filtered_objects;

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
                        $lab_object_count[$lab_name] = 0;
                    }
                    $lab_object_count[$lab_name] += $nb_filtered_objects;
                }

                // add study
                $study_title = isset($sample->study_title) ? $sample->study_title : '';
                if ($study_title != '') {
                    if (! in_array($study_title, $study_list)) {
                        $study_list[] = $study_title;
                        $study_object_count[$study_title] = 0;
                    }
                    $study_object_count[$study_title] += $nb_filtered_objects;
                }
            }
        }

        $study_tree = [];
        $lab_data = [];
        $new_study_data = [];

        foreach ($sample_list as $sample) {
            // lab name
            $lab = isset($sample->lab_name) ? $sample->lab_name : '';

            // lab
            if (! isset($study_tree[$lab])) {
                $lab_data['name'] = $lab;
                $lab_data['total_object_count'] = isset($lab_object_count[$lab]) ? $lab_object_count[$lab] : 0;
                $study_tree[$lab] = $lab_data;
            }

            // study title
            if (! isset($sample->study_title)) {
                $sample->study_title = '';
            }
            $new_study_data['study_title'] = $sample->study_title;

            // total sequences
            $new_study_data['total_object_count'] = isset($study_object_count[$sample->study_title]) ? $study_object_count[$sample->study_title] : 0;

            // study url
            if (isset($sample->study_url)) {
                $new_study_data['study_url'] = $sample->study_url;
            }

            // add study to tree
            $study_tree[$lab]['studies'][$sample->study_title] = $new_study_data;
        }

        $rs_data = [];

        $rs_data['total_samples'] = count($sample_list);
        $rs_data['total_labs'] = count($lab_list);
        $rs_data['total_studies'] = count($study_list);
        $rs_data['total_object_count'] = $total_object_count;
        $rs_data['total_filtered_objects'] = $total_filtered_objects;
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
        $total_filtered_objects = 0;

        foreach ($data['rs_list'] as $rs_data) {
            if ($rs_data['total_samples'] > 0) {
                $filtered_repositories[] = $rs_data['rs'];
                $total_filtered_repositories++;
            }

            $total_filtered_samples += $rs_data['total_samples'];
            $total_filtered_labs += $rs_data['total_labs'];
            $total_filtered_studies += $rs_data['total_studies'];
            $total_filtered_objects += $rs_data['total_filtered_objects'];
        }

        // sort alphabetically repositories/labs/studies
        $data['rs_list'] = Sample::sort_rest_service_list($data['rs_list']);

        $data['total_filtered_samples'] = $total_filtered_samples;
        $data['total_filtered_repositories'] = $total_filtered_repositories;
        $data['total_filtered_labs'] = $total_filtered_labs;
        $data['total_filtered_studies'] = $total_filtered_studies;
        $data['total_filtered_objects'] = $total_filtered_objects;
        $data['filtered_repositories'] = $filtered_repositories;

        return $data;
    }

    public static function generate_repertoire_query_file($folder_path, $sample_filters)
    {
        // Save the info into the repertoire_query.json file.
        $repertoire_query_file_path = $folder_path . '/repertoire_query.json';
        Log::debug('generate_repertoire_query_file: writing ' . $repertoire_query_file_path);
        $json_query = RestService::generate_json_query($sample_filters);
        Log::debug('generate_repertoire_query_file: contents: ' . $json_query);
        file_put_contents($repertoire_query_file_path, $json_query);

        return $repertoire_query_file_path;
    }

    public static function generate_rearrangement_query_file($folder_path, $filters)
    {
        // Save the info into the repertoire_query.json file.
        $rearrangement_query_file_path = $folder_path . '/rearrangement_query.json';
        Log::debug('generate_rearrangement_query_file: writing ' . $rearrangement_query_file_path);

        // build list of sequence filters only (remove sample id filters)
        $sequence_filters = $filters;
        //unset($sequence_filters['project_id_list']);
        foreach ($sequence_filters as $key => $value) {
            if (starts_with($key, 'ir_project_sample_id_list_')) {
                unset($sequence_filters[$key]);
            }
        }

        $json_query = RestService::generate_json_query($sequence_filters);
        Log::debug('generate_rearrangement_query_file: contents: ' . $json_query);
        file_put_contents($rearrangement_query_file_path, $json_query);

        return $rearrangement_query_file_path;
    }

    public static function generate_info_file($folder_path, $url, $sample_filters, $filters, $file_stats, $username, $now, $failed_rs)
    {
        // Set name of info file
        $info_file = 'info.txt';

        // Initialize string.
        $s = '';
        // We want to extract the query_id from the URL. The URL has a query_id parameter
        // wich we need to extract. URLs look like this:
        // https:\/\/gateway-analysis.ireceptor.org\/sequences?query_id=8636
        $query_sep = 'query_id=';
        // Get the query_id, it should be everything after the query_sep string.
        $seq_query_id = substr($url, strpos($url, $query_sep) + strlen($query_sep));
        // Get the query parameters for both the sequence and sample queries. The
        // sequence query parameters contains the query id of the sample query used
        // to get to the seqeunces page.
        $seq_query_params = Query::getParams($seq_query_id);
        if (array_key_exists('sample_query_id', $seq_query_params)) {
            $sam_query_id = $seq_query_params['sample_query_id'];
            $sam_query_params = Query::getParams($sam_query_id);
            $sam_summary = Query::sampleParamsSummary($sam_query_params);
        } else {
            $sam_summary = 'None';
        }

        // Use the Query class to generate a consistent set of summary info
        // from the query parameters. This returns a single string, containing
        // a set of lines for each parameter (with \n), which is what we want.
        $s .= '<p><strong>Metadata filters</strong></p>' . "\n";
        $s .= "<p>\n";
        // Replace each newline with a HTML <br> followed by the newline as
        // we want HTML here.
        $sam_summary = str_replace("\n", "<br>\n", $sam_summary);
        $s .= $sam_summary;
        $s .= "</p>\n";

        $s .= '<p><strong>Sequence filters</strong></p>' . "\n";
        $s .= "<p>\n";
        $seq_summary = Query::sequenceParamsSummary($seq_query_params);
        // Replace each newline with a HTML <br> followed by the newline as
        // we want HTML here.
        $seq_summary = str_replace("\n", "<br>\n", $seq_summary);
        $s .= $seq_summary;
        $s .= "</p>\n";

        // Generate a summary of the repositories used.
        $s .= '<p><strong>Data/Repository Summary</strong></p>' . "\n";
        $s .= "<p>\n";

        $nb_sequences_total = 0;
        $expected_nb_sequences_total = 0;
        foreach ($file_stats as $t) {
            $nb_sequences_total += $t['nb_sequences'];
            $expected_nb_sequences_total += $t['expected_nb_sequences'];
        }

        $is_download_incomplete = ($nb_sequences_total < $expected_nb_sequences_total);
        if ($is_download_incomplete) {
            $s .= 'GW-ERROR: some of the downloaded files appear to be incomplete:' . "<br>\n";
            $s .= 'Total: ' . $nb_sequences_total . ' sequences, but ' . $expected_nb_sequences_total . ' were expected.' . "<br>\n";
        } else {
            $s .= 'Total: ' . $nb_sequences_total . ' sequences' . "<br>\n";
        }

        foreach ($file_stats as $t) {
            if ($is_download_incomplete && ($t['nb_sequences'] < $t['expected_nb_sequences'])) {
                $s .= 'GW-ERROR: ' . $t['name'] . ' (' . $t['size'] . '): ' . $t['nb_sequences'] . ' sequences (incomplete, expected ' . $t['expected_nb_sequences'] . ' sequences) (from ' . $t['rs_url'] . ')' . "<br>\n";
            } else {
                $s .= $t['name'] . ' (' . $t['size'] . '): ' . $t['nb_sequences'] . ' sequences (from ' . $t['rs_url'] . ')' . "<br>\n";
            }
        }

        if (! empty($failed_rs)) {
            $s .= 'GW-ERROR: some files are missing because an error occurred while downloading sequences from these repositories:' . "<br>\n";
            foreach ($failed_rs as $rs) {
                $s .= 'GW-ERROR: ' . $rs->name . "<br>\n";
            }
        }
        $s .= "</p>\n";

        // Generate a summary of where the query came from.
        $s .= '<p><strong>Source</strong></p>' . "\n";
        $s .= "<p>\n";
        $date_str_human = date('M j, Y', $now);
        $time_str_human = date('H:i T', $now);
        $s .= 'Downloaded by ' . $username . ' on ' . $date_str_human . ' at ' . $time_str_human . "<br>\n";
        $s .= "</p>\n";

        // Save the info into the info file.
        $info_file_path = $folder_path . '/' . $info_file;
        file_put_contents($info_file_path, $s);

        return $info_file_path;
    }

    public static function generate_manifest_file($folder_path, $url, $sample_filters, $filters, $file_stats, $username, $now, $failed_rs)
    {
        $manifest = new \stdClass();

        // info section
        $manifest->Info = new \stdClass();

        $manifest->Info->title = 'AIRR Manifest';
        $manifest->Info->version = '3.0';
        $manifest->Info->description = 'List of files for each repository';

        $manifest->Info->contact = new \stdClass();
        $manifest->Info->contact->name = config('app.name');
        $manifest->Info->contact->url = config('app.url');
        $manifest->Info->contact->email = config('ireceptor.email_support');

        // datasets section
        $manifest->DataSets = [];

        foreach ($file_stats as $t) {
            $dataset = new \stdClass();

            $dataset->repository = $t['rest_service_name'];
            $dataset->repository_url = $t['rs_url'];

            if (isset($t['metadata_file_name'])) {
                $dataset->repertoire_file = $t['metadata_file_name'];
            }

            if (isset($t['sequence_file_name'])) {
                $dataset->rearrangement_file = $t['sequence_file_name'];
            }

            if (isset($t['clone_file_name'])) {
                $dataset->clone_file = $t['clone_file_name'];
            }

            if (isset($t['cell_file_name'])) {
                $dataset->cell_file = $t['cell_file_name'];
            }
            if (isset($t['expression_file_name'])) {
                $dataset->expression_file = $t['expression_file_name'];
            }

            $manifest->DataSets[] = $dataset;
        }

        // generate JSON
        $manifest_json = json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $manifest_file_path = $folder_path . '/' . 'AIRR-manifest.json';
        file_put_contents($manifest_file_path, $manifest_json);

        return $manifest_file_path;
    }

    public static function zip_files($folder_path, $response_list, $metadata_response_list, $info_file_path, $manifest_file_path, $repertoire_query_file_path, $rearrangement_query_file_path)
    {
        $zipPath = $folder_path . '.zip';
        Log::info('Zip files to ' . $zipPath);
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE);

        // sequence data
        foreach ($response_list as $response) {
            if (isset($response['data']['file_path'])) {
                $file_path = $response['data']['file_path'];
                Log::debug('Adding to ZIP: ' . $file_path);
                $zip->addFile($file_path, basename($file_path));
            }
        }

        // repertoire data
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

        // Info file
        Log::debug('Adding to ZIP: ' . $info_file_path);
        $zip->addFile($info_file_path, basename($info_file_path));

        // AIRR-manifest.json
        $zip->addFile($manifest_file_path, basename($manifest_file_path));
        Log::debug('Adding to ZIP: ' . $manifest_file_path);

        // Repertoire query file
        $zip->addFile($repertoire_query_file_path, basename($repertoire_query_file_path));
        Log::debug('Adding to ZIP: ' . $repertoire_query_file_path);

        // Rearrangement query file
        $zip->addFile($rearrangement_query_file_path, basename($rearrangement_query_file_path));
        Log::debug('Adding to ZIP: ' . $rearrangement_query_file_path);

        $zip->close();

        return $zipPath;
    }

    public static function delete_files($folder_path)
    {
        // Check to see if the path exists.
        if (File::exists($folder_path)) {
            // Check to see if the path is within the file space of Laravel managed
            // storage. This is a paranoid check to make sure we don't remove everything
            // on the system 8-)
            if (str_contains($folder_path, storage_path())) {
                Log::debug('Deleting folder of downloaded files: ' . $folder_path);
                exec(sprintf('rm -rf %s', escapeshellarg($folder_path)));
            } else {
                Log::error('Could not delete folder ' . $folder_path);
                Log::error('Folder is not in the Laravel storage area ' . storage_path());
            }
        }
    }

    public static function file_stats($response_list, $metadata_response_list, $expected_nb_sequences_by_rs)
    {
        Log::debug('Get TSV files stats');
        $file_stats = [];
        foreach ($response_list as $response) {
            $rest_service_id = $response['rs']->id;

            if (isset($response['data']['file_path'])) {
                $t = [];
                $file_path = $response['data']['file_path'];
                $t['name'] = basename($file_path);
                $t['rs_url'] = $response['rs']->url;
                $t['size'] = human_filesize($file_path);

                // sequence file name
                $t['sequence_file_name'] = $t['name'];

                // repertoire file name
                foreach ($metadata_response_list as $r) {
                    if ($rest_service_id == $r['rs']->id) {
                        if (isset($r['data']['file_path'])) {
                            $repertoire_file_path = $r['data']['file_path'];
                            $t['metadata_file_name'] = basename($repertoire_file_path);
                        }
                    }
                }

                // count number of lines
                Log::debug('Get TSV files stats for ' . $file_path);
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
                Log::debug('Counts: total = ' . $t['nb_sequences'] . ' expected = ' . $t['expected_nb_sequences']);
                $t['query_log_id'] = $response['query_log_id'];
                $t['rest_service_name'] = $response['rs']->name;
                $t['incomplete'] = ($t['nb_sequences'] != $t['expected_nb_sequences']);

                $file_stats[] = $t;
            }
        }

        return $file_stats;
    }
}
