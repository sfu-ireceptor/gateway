<?php

namespace App;

use Facades\App\RestService;
use Facades\App\Sequence;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class SequenceClone
{
    public static function summary($filters, $username)
    {
        // get clones summary
        $response_list_clones_summary = RestService::sequences_summary($filters, $username, true, 'clone');

        // generate stats
        $data = self::process_response($response_list_clones_summary);

        // get a few clones from each service
        $response_list = RestService::sequence_list($filters, $response_list_clones_summary, 10, 'clone');

        // merge responses
        $clone_list = [];
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

            $rs_clone_list = data_get($obj, 'Clone', []);

            // convert any array properties to strings
            $rs_clone_list = array_map('convert_arrays_to_strings', $rs_clone_list);

            // convert fields
            $rs_clone_list = FieldName::convertObjectList($rs_clone_list, 'ir_adc_api_response', 'ir_id', 'Clone', $rs->api_version);

            $clone_list = array_merge($clone_list, $rs_clone_list);
        }

        // add to stats data
        $data['items'] = $clone_list;

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

    public static function expectedSequenceClonesByRestSevice($filters, $username)
    {
        $response_list = RestService::sequences_summary($filters, $username, false, 'clone');
        $expected_nb_clones_by_rs = [];
        foreach ($response_list as $response) {
            $rest_service_id = $response['rs']->id;

            $nb_clones = 0;
            if (isset($response['data'])) {
                $sample_list = $response['data'];
                foreach ($sample_list as $sample) {
                    if (isset($sample->ir_filtered_clone_count)) {
                        $nb_clones += $sample->ir_filtered_clone_count;
                    }
                }
            }

            $expected_nb_clones_by_rs[$rest_service_id] = $nb_clones;
        }

        return $expected_nb_clones_by_rs;
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
                    if (isset($sample->ir_filtered_clone_count) && ($sample->ir_filtered_clone_count > 0)) {
                        $sample_id_list[] = $sample->repertoire_id;
                    }
                }
            }

            $filtered_samples_by_rs[$rest_service_id] = $sample_id_list;
        }

        return $filtered_samples_by_rs;
    }

    public static function clonesTSVFolder($filters, $username, $url = '',
                                           $sample_filters = [], $download_data)
    {
        // allow more time than usual for this request
        set_time_limit(config('ireceptor.gateway_file_request_timeout'));

        // do extra clone summary request
        $response_list = RestService::sequences_summary($filters, $username, false, 'clone');

        // do extra clone summary request to get expected number of clones
        // for sanity check after download
        $expected_nb_clones_by_rs = self::expectedSequenceClonesByRestSevice($filters, $username);

        // get filtered list of repertoires ids
        $filtered_samples_by_rs = self::filteredSamplesByRestService($response_list);

        // if total expected nb clones is 0, immediately fail download
        $total_expected_nb_clones = 0;
        foreach ($expected_nb_clones_by_rs as $rs => $count) {
            $total_expected_nb_clones += $count;
        }
        if ($total_expected_nb_clones <= 0) {
            throw new \Exception('No clones to download');
        }

        // if total expected nb clones > download limit, immediately fail download
        $clones_download_limit = config('ireceptor.clones_download_limit');
        if ($total_expected_nb_clones > $clones_download_limit) {
            throw new \Exception('Trying to download to many clones: ' . $total_expected_nb_clones . ' > ' . $clones_download_limit);
        }

        // Full path of receiving folder for the download data, based on the
        // download_data_folder config variable, which is relative to the
        // Laravel storage_path().
        $storage_folder = storage_path() . '/' . config('ireceptor.downloads_data_folder') . '/';

        $now = time();
        $time_str = date('Y-m-d_Hi', $now);
        $base_name = 'ir_' . $time_str . '_' . uniqid();
        $folder_path = $storage_folder . $base_name;
        File::makeDirectory($folder_path, 0777, true, true);

        $metadata_response_list = RestService::sample_list_repertoire_data($filtered_samples_by_rs, $folder_path, $username);
        $clone_response_list = [];
        if ($download_data) {
            $clone_response_list = RestService::clones_data($filters, $folder_path, $username, $expected_nb_clones_by_rs);
        } else {
            Log::debug('Sequence::sequencesTSVFolder - SKIPPING DOWNLOAD');
        }

        // Get a list of file information as a block of data.
        $file_stats = self::file_stats($clone_response_list, $metadata_response_list, $expected_nb_clones_by_rs, $download_data);

        // if some files are incomplete, log it
        foreach ($file_stats as $t) {
            if ($t['nb_clones'] != $t['expected_nb_clones']) {
                $delta = ($t['expected_nb_clones'] - $t['nb_clones']);
                $str = 'expected ' . $t['expected_nb_clones'] . ' clones, got ' . $t['nb_clones'] . ' clones (difference=' . $delta . ' clones)';
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
        foreach ($clone_response_list as $response) {
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
        $nb_clones_total = 0;
        $expected_nb_clones_total = 0;
        foreach ($file_stats as $t) {
            $nb_clones_total += $t['nb_clones'];
            $expected_nb_clones_total += $t['expected_nb_clones'];
        }
        if ($nb_clones_total < $expected_nb_clones_total) {
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
                foreach ($clone_response_list as $response) {
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
                } else {
                    $download_incomplete_info .= 'Downloads from the following repositories finished successfully: ' . $rs_name_list_str . ".\n";
                }
            } else {
                $download_incomplete_info .= 'Some files appear to be incomplete. See the included Info file for more details.';
            }
        }

        // generate info file
        $info_file_path = self::generate_info_file($folder_path, $url, $sample_filters, $filters, $file_stats, $username, $now, $failed_rs);
        // generate manifest.json
        $manifest_file_path = Sequence::generate_manifest_file($folder_path, $url, $sample_filters, $filters, $file_stats, $username, $now, $failed_rs);

        $t = [];
        $t['base_path'] = $storage_folder;
        $t['base_name'] = $base_name;
        $t['folder_path'] = $folder_path;
        $t['response_list'] = $clone_response_list;
        $t['metadata_response_list'] = $metadata_response_list;
        $t['info_file_path'] = $info_file_path;
        $t['manifest_file_path'] = $manifest_file_path;
        $t['is_download_incomplete'] = $is_download_incomplete;
        $t['download_incomplete_info'] = $download_incomplete_info;
        $t['file_stats'] = $file_stats;

        return $t;
    }

    public static function clonesTSV($filters, $username, $url = '', $sample_filters = [], $download_data = true)
    {
        $t = self::clonesTSVFolder($filters, $username, $url, $sample_filters, $download_data);

        $base_path = $t['base_path'];
        $base_name = $t['base_name'];
        $folder_path = $t['folder_path'];
        $clone_response_list = $t['response_list'];
        $metadata_response_list = $t['metadata_response_list'];
        $info_file_path = $t['info_file_path'];
        $manifest_file_path = $t['manifest_file_path'];
        $is_download_incomplete = $t['is_download_incomplete'];
        $download_incomplete_info = $t['download_incomplete_info'];
        $file_stats = $t['file_stats'];

        // zip files
        $zip_path = self::zip_files($folder_path, $clone_response_list, $metadata_response_list, $info_file_path, $manifest_file_path, $download_data);

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
            // clone count for that sample
            if (isset($sample->ir_clone_count)) {
                $total_object_count += $sample->ir_clone_count;
            }

            // filtered clone count for that sample
            if (isset($sample->ir_filtered_clone_count)) {
                $nb_filtered_clones = $sample->ir_filtered_clone_count;
                $total_filtered_objects += $nb_filtered_clones;

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
                    $lab_object_count[$lab_name] += $nb_filtered_clones;
                }

                // add study
                $study_title = isset($sample->study_title) ? $sample->study_title : '';
                if ($study_title != '') {
                    if (! in_array($study_title, $study_list)) {
                        $study_list[] = $study_title;
                        $study_object_count[$study_title] = 0;
                    }
                    $study_object_count[$study_title] += $nb_filtered_clones;
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

            // total clones
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

    public static function generate_info_file($folder_path, $url, $sample_filters, $filters, $file_stats, $username, $now, $failed_rs)
    {
        // Set info file.
        $info_file = 'info.txt';

        // Initialize string.
        $s = '';

        $s .= '<p><b>Metadata filters</b></p>' . "\n";
        Log::debug($sample_filters);
        if (count($sample_filters) == 0) {
            $s .= 'None' . "<br/>\n";
        }
        foreach ($sample_filters as $k => $v) {
            if (is_array($v)) {
                $v = implode(' or ', $v);
            }
            // use human-friendly filter name
            $s .= __('short.' . $k) . ': ' . $v . "<br/>\n";
        }
        $s .= "<br/>\n";

        $s .= '<p><b>Clone filters</b></p>' . "\n";
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
            $s .= 'None' . "<br/>\n";
        }
        foreach ($filters as $k => $v) {
            if (is_array($v)) {
                $v = implode(' or ', $v);
            }
            // use human-friendly filter name
            $s .= __('short.' . $k) . ': ' . $v . "<br/>\n";
        }
        $s .= "<br/>\n";

        $s .= '<p><b>Data/Repository Summary</b></p>' . "\n";

        $nb_clones_total = 0;
        $expected_nb_clones_total = 0;
        foreach ($file_stats as $t) {
            $nb_clones_total += $t['nb_clones'];
            $expected_nb_clones_total += $t['expected_nb_clones'];
        }

        $is_download_incomplete = ($nb_clones_total < $expected_nb_clones_total);
        if ($is_download_incomplete) {
            $s .= 'GW-ERROR: some of the files appears to be incomplete:' . "<br/>\n";
            $s .= 'Total: ' . $nb_clones_total . ' clones, but ' . $expected_nb_clones_total . ' were expected.' . "<br/>\n";
        } else {
            $s .= 'Total: ' . $nb_clones_total . ' clones' . "<br/>\n";
        }

        foreach ($file_stats as $t) {
            if ($is_download_incomplete && ($t['nb_clones'] < $t['expected_nb_clones'])) {
                $s .= 'GW-ERROR: ' . $t['name'] . ' (' . $t['size'] . '): ' . $t['nb_clones'] . ' clones (incomplete, expected ' . $t['expected_nb_clones'] . ' clones) (from ' . $t['rs_url'] . ')' . "<br/>\n";
            } else {
                $s .= $t['name'] . ' (' . $t['size'] . '): ' . $t['nb_clones'] . ' clones (from ' . $t['rs_url'] . ')' . "<br/>\n";
            }
        }

        if (! empty($failed_rs)) {
            $s .= 'GW-ERROR: some files are missing because an error occurred while downloading clones from these repositories:' . "<br/>\n";
            foreach ($failed_rs as $rs) {
                $s .= 'GW-ERROR: ' . $rs->name . "<br/>\n";
            }
        }

        $s .= "<br/>\n";

        $s .= '<p><b>Source</b></p>' . "\n";
        $s .= $url . "<br/>\n";
        $date_str_human = date('M j, Y', $now);
        $time_str_human = date('H:i T', $now);
        $s .= 'Downloaded by ' . $username . ' on ' . $date_str_human . ' at ' . $time_str_human . "<br/>\n";

        $info_file_path = $folder_path . '/' . $info_file;
        file_put_contents($info_file_path, $s);

        return $info_file_path;
    }

    public static function zip_files($folder_path, $cell_response_list, $metadata_response_list, $info_file_path, $manifest_file_path, $download_file)
    {
        $zipPath = $folder_path . '.zip';
        Log::info('Zip files to ' . $zipPath);
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE);

        // clone data
        foreach ($cell_response_list as $response) {
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
        $zip->addFile($info_file_path, basename($info_file_path));

        // AIRR-manifest.json
        $zip->addFile($manifest_file_path, basename($manifest_file_path));
        Log::debug('Adding to ZIP: ' . $manifest_file_path);

        $zip->close();

        return $zipPath;
    }

    public static function delete_files($folder_path)
    {
        Log::debug('Deleting downloaded files...');
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

    public static function file_stats($cell_response_list, $metadata_response_list, $expected_nb_clones_by_rs, $download_data)
    {
        Log::debug('Get TSV files stats');
        // Process the correct response list, depending on if we are downloading
        // data or not.
        if ($download_data) {
            $response_list = $cell_response_list;
        } else {
            $response_list = $metadata_response_list;
        }

        $file_stats = [];
        foreach ($response_list as $response) {
            $rest_service_id = $response['rs']->id;

            if (isset($response['data']['file_path'])) {
                $t = [];
                $file_path = $response['data']['file_path'];
                $t['name'] = basename($file_path);
                $t['rs_url'] = $response['rs']->url;
                $t['size'] = human_filesize($file_path);

                if ($download_data) {
                    // If we are downloading, we need to...

                    // clone file name
                    $t['clone_file_name'] = $t['name'];

                    // repertoire file name
                    foreach ($metadata_response_list as $r) {
                        if ($rest_service_id == $r['rs']->id) {
                            if (isset($r['data']['file_path'])) {
                                $repertoire_file_path = $r['data']['file_path'];
                                $t['metadata_file_name'] = basename($repertoire_file_path);
                            }
                        }
                    }

                    // count number of times the AIRR clone_id field occurs in the file.
                    $n = 0;
                    $f = fopen($file_path, 'r');
                    while (! feof($f)) {
                        $line = fgets($f);
                        if (! empty($line)) {
                            $n += substr_count($line, '"clone_id"');
                        }
                    }
                    fclose($f);
                    $t['nb_clones'] = $n;

                    // Count the number of expected lines from the service list.
                    $t['expected_nb_clones'] = 0;
                    if (isset($expected_nb_clones_by_rs[$rest_service_id])) {
                        $t['expected_nb_clones'] = $expected_nb_clones_by_rs[$rest_service_id];
                    } else {
                        Log::error('rest_service ' . $rest_service_id . ' is missing from $expected_nb_clones_by_rs array');
                        Log::error($expected_nb_clones_by_rs);
                    }
                } else {
                    // The metadata/repertoire file name is just the name
                    $t['metadata_file_name'] = $t['name'];

                    // If we aren't downloading we expect and got 0 clones
                    $t['nb_clones'] = 0;
                    $t['expected_nb_clones'] = 0;
                }

                $t['query_log_id'] = $response['query_log_id'];
                $t['rest_service_name'] = $response['rs']->name;
                $t['incomplete'] = ($t['nb_clones'] != $t['expected_nb_clones']);

                $file_stats[] = $t;
            }
        }

        return $file_stats;
    }
}
