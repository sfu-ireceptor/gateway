<?php

namespace App;

use Facades\App\RestService;
use Facades\App\Sequence;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class Cell
{
    // Returns a complex object of the form:
    // rs_list - array of repositories and related info that were queried
    // rs_list_no_response - array of repositories that didn't respond
    // rs_list_no_response_timeout - array of repositories that timed out
    // rs_list_no_response_error - array of repositories that had errors
    // summary - array of repertoires that meet the search criteria
    // total_filtered_samples - total number of filtered samples
    // total_filtered_repositories - total number of filtered repositories
    // total_filtered_labs - total number of filtered labs
    // total_filtered_studies - total number of filtered studies
    // total_filtered_objects - total number of filtered objects
    // filtered_repositories - array of filtered repositories in which data was found
    // items - array of filtered objects that were found
    public static function summary($filters, $username)
    {
        // Convert the service repertoire lists from the UI filters into an associative
        // array with key the ID and the contents an array of repertoire_ids.
        $service_repertoire_list = Cell::getServiceRepertoires($filters);

        // Get the object specific (cell, expression, reactivity) filters from
        // the generic filter list from the UI.
        $object_filters = Cell::getCellObjectFilters($filters);

        // Get the list of Cell IDs from all of the services/repertoirs
        // that meet the cell, expression, and reactivity filters. If there
        // are no filters for any of cell, expression, or reactivity, then we
        // simply search for all cells (there are no cell level filters). Because
        // cell IDs are globally unique, they can be searched for across repositories
        // without conflict so we can gather them all together. This is inefficient 
        // as it would be better to search a repository for only those cells that are
        // in that repository, but such a search is more complicated.
        // TODO: Optimize the search by service.
        $all_cell_ids = [];
        $num_filters = count($object_filters['cell']) + 
            count($object_filters['expression']) + 
            count($object_filters['reactivity']);
        Log::debug('Cell:summary - Getting Cell IDs, number of filters = ' . $num_filters);

        // If we don't have any filters, then we want all cells - which is the
        // equivalent of searching with no cell id filters.
        $all_cell_ids = [];
        if ($num_filters > 0) {

            $cell_ids_by_rs = Cell::getCellIDByRS($service_repertoire_list,
                $object_filters['cell'],
                $object_filters['expression'],
                $object_filters['reactivity']);

            // Because cell IDs are globally unique, they can be searched for across
            // repositories without conflict. So we combine them
            foreach ($cell_ids_by_rs as $rs => $rs_cell_id_array) {
                $all_cell_ids = array_merge($all_cell_ids, $rs_cell_id_array);
            }
        }
        Log::debug('Cell:summary - Number of Cell IDs = ' . count($all_cell_ids));

        // Remove the cell specific filter fields from the UI.
        // TODO: This may be unnecessary, we should be able to just create
        // a clean new filter list.
        unset($filters['cell_id_cell']);
        unset($filters['expression_study_method_cell']);
        unset($filters['virtual_pairing_cell']);
        unset($filters['property_expression']);
        unset($filters['value_expression']);
        unset($filters['antigen_reactivity']);
        unset($filters['antigen_source_species_reactivity']);
        unset($filters['peptide_sequence_aa_reactivity']);

        // If there were filters, and there are no cell ids, then we didn't
        // find anything that matched our criteria so there are no results.
        // If there are results (filters need to be applied) and we have some
        // cell_ids then we process the data.
        $response_list = [];
        $response_list_cells_summary = [];
        if ($num_filters > 0 && count($all_cell_ids) > 0) {
            // If there are cell ID filters, set them.
            $filters['cell_id_cell'] = $all_cell_ids;
        }

        // Get repertoire summary for the cells that meet the criteria.
        $response_list_cells_summary = RestService::data_summary($filters, $username, true, 'cell');

        // Get a few cells from each service
        $response_list = RestService::data_subset($filters, $response_list_cells_summary, 10, 'cell');

        // generate repertoire stats for the graphs
        $data = self::process_response($response_list_cells_summary);

        // Merge responses into a single list of Cells
        $cell_list = [];
        foreach ($response_list as $response) {
            $rs = $response['rs'];

            // If error, add to list of problematic repositories
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

            // Get the list of Cells from the response.
            $obj = $response['data'];
            $rs_cell_list = data_get($obj, 'Cell', []);

            // Convert fields based on the API version.
            $rs_cell_list = FieldName::convertObjectList($rs_cell_list, 'ir_adc_api_response', 'ir_id', 'Cell', $rs->api_version);

            // Merge the list so that we have a single list of all cells to display.
            $cell_list = array_merge($cell_list, $rs_cell_list);
        }

        // Add to stats data
        $data['items'] = $cell_list;

        // Split list of servers which didn't respond by "timeout" or "error"
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

    // Return an array, keyed by service ID, with each array containing
    // a list of repertoire IDs from a query filter.
    public static function getServiceRepertoires($filters)
    {
        // Convert the service repertoire lists into an associative array with key
        // the service ID and the contents an array of repertoire_ids. The filter contains
        // arrays with a key ir_project_sample_id_list_NN where NN is the numeric
        // identifier for the service.
        $service_repertoire_list = [];
        foreach ($filters as $key => $value) {
            if (strrpos($key, 'ir_project_sample_id_list') !== false) {
                // Use everything after the last "_" as the ID
                $id_str = substr($key, strrpos($key, '_') + 1);
                // Add the list of repertoires to the array with the id as the key
                $service_repertoire_list[$id_str] = $value;
            }
        }

        return $service_repertoire_list;
    }

    // Given a set of filters from the Gateway, extract the filters that are
    // applicable to a given type of object/endpoint (cell, expression, reactivity)
    // and return the filters in an array keyed by the service endpoint they apply to.
    public static function getCellObjectFilters($filters)
    {
        // Extract the cell, expression, and reactivity specific filters.
        $cell_filters = [];
        $expression_filters = [];
        $reactivity_filters = [];
        foreach ($filters as $key => $value) {
            // Each key has the filter type encoded in the name after the last "_", as in
            // virtual_pairing_cell is a virtual_pairing filter of type cell
            $sep_location = strrpos($key, '_');
            // For each type of filter, add it to the filter list
            if ($sep_location !== false) {
                $filter_type = substr($key, $sep_location + 1);
                if ($filter_type == 'cell' && $value != null) {
                    $cell_filters[$key] = $value;
                } elseif ($filter_type == 'expression' && $value != null) {
                    $expression_filters[$key] = $value;
                } elseif ($filter_type == 'reactivity' && $value != null) {
                    $reactivity_filters[$key] = $value;
                }
            }
        }
        // Create an associative array for each type of filter.
        $object_filters = [];
        $object_filters['reactivity'] = $reactivity_filters;
        $object_filters['expression'] = $expression_filters;
        $object_filters['cell'] = $cell_filters;

        return $object_filters;
    }

    public static function getCellIDByRS($service_repertoire_list, $cell_filters,
        $expression_filters, $reactivity_filters)
    {
        // Create arrays of cell ids (keyed by service id) that are retrieved from
        // each of the cell, expression, and reactivity filters.
        $cell_ids_by_rs = [];
        $expression_cell_ids_by_rs = [];
        $reactivity_cell_ids_by_rs = [];

        // Get the initial set of cell ids from the cell filters.
        Log::debug('Cell::getCellIDbyRS - searching cells');
        $cell_ids_by_rs = RestService::object_list('cell', $service_repertoire_list, $cell_filters, 'cell_id');

        // If we have expression filters apply them, get the list of cells, and
        // intersect them with the current list.
        Log::debug('Cell::getCellIDbyRS - searching expression');
        if (count($expression_filters) > 0) {
            $expression_cell_ids_by_rs = RestService::object_list('expression', $service_repertoire_list,
                $expression_filters, 'cell_id');
            // We need to loop over each service and merge cell_ids per service
            foreach ($expression_cell_ids_by_rs as $rs => $cell_array) {
                // If this service already has data, intersect, otherwise just add
                if (array_key_exists($rs, $cell_ids_by_rs)) {
                    $cell_ids_by_rs[$rs] = array_intersect($cell_ids_by_rs[$rs],
                        $expression_cell_ids_by_rs[$rs]);
                } 
            }
        }

        // If we have reactivity filters apply them, get the list of cells, and
        // intersect them with the current list.
        Log::debug('Cell::getCellIDbyRS - searching reactivity');
        if (count($reactivity_filters) > 0) {
            $reactivity_cell_ids_by_rs = RestService::object_list('reactivity', $service_repertoire_list,
                $reactivity_filters, 'cell_id');
            // We need to loop over each service and merge cell_ids per service
            foreach ($reactivity_cell_ids_by_rs as $rs => $cell_array) {
                // If this service already has data, intersect, otherwise just add
                if (array_key_exists($rs, $cell_ids_by_rs)) {
                    $cell_ids_by_rs[$rs] = array_intersect($cell_ids_by_rs[$rs],
                        $reactivity_cell_ids_by_rs[$rs]);
                } 
            }
        }
        // We want a non associative array, the merge above maintains
        // the associative keys from the original arrays.
        foreach ($cell_ids_by_rs as $rs_id => $cell_ids_for_rs) {
            $cell_ids_by_rs[$rs_id] = array_values($cell_ids_for_rs);
            Log::debug('Cell::getCellIDbyRS - RS = ' . $rs_id . ' cell id count = ' . count($cell_ids_by_rs[$rs_id]));
        }

        // Return the array keyed by service id.
        return $cell_ids_by_rs;
    }

    public static function expectedCellsByRestSevice($response_list)
    {
        $expected_nb_cells_by_rs = [];
        foreach ($response_list as $response) {
            $rest_service_id = $response['rs']->id;

            $nb_cells = 0;
            if (isset($response['data'])) {
                $sample_list = $response['data'];
                foreach ($sample_list as $sample) {
                    if (isset($sample->ir_filtered_cell_count)) {
                        $nb_cells += $sample->ir_filtered_cell_count;
                    }
                }
            }

            $expected_nb_cells_by_rs[$rest_service_id] = $nb_cells;
        }

        return $expected_nb_cells_by_rs;
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
                    if (isset($sample->ir_filtered_cell_count) && ($sample->ir_filtered_cell_count > 0)) {
                        $sample_id_list[] = $sample->repertoire_id;
                    }
                }
            }

            $filtered_samples_by_rs[$rest_service_id] = $sample_id_list;
        }

        return $filtered_samples_by_rs;
    }

    public static function cellsTSVFolder($filters, $username, $url = '',
                                          $sample_filters = [], $download_data)
    {
        // allow more time than usual for this request
        set_time_limit(config('ireceptor.gateway_file_request_timeout'));

        // Convert the service repertoire lists from the UI filters into an associative
        // array with key the ID and the contents an array of repertoire_ids.
        $service_repertoire_list = Cell::getServiceRepertoires($filters);

        // Get the object specific (cell, expression, reactivity) filters from
        // the generic filter list from the UI.
        $object_filters = Cell::getCellObjectFilters($filters);

        // Get the list of Cell IDs from all of the services/repertoirs
        // that meet the cell, expression, and reactivity filters. If there
        // are no filters for any of cell, expression, or reactivity, then we
        // simply search for all cells (there are no cell level filters). Because
        // cell IDs are globally unique, they can be searched for across repositories
        // without conflict so we can gather them all together. This is inefficient
        // as it would be better to search a repository for only those cells that are
        // in that repository, but such a search is more complicated.
        // TODO: Optimize the search by service.
        $all_cell_ids = [];
        $num_filters = count($object_filters['cell']) +
            count($object_filters['expression']) +
            count($object_filters['reactivity']);
        Log::debug('Cell:cellsTSVFolder - Getting Cell IDs, number of filters = ' . $num_filters);

        // If we don't have any filters, then we want all cells - which is the
        // equivalent of searching with no cell id filters.
        $all_cell_ids = [];
        if ($num_filters > 0) {

            $cell_ids_by_rs = Cell::getCellIDByRS($service_repertoire_list,
                $object_filters['cell'],
                $object_filters['expression'],
                $object_filters['reactivity']);

            // Because cell IDs are globally unique, they can be searched for across
            // repositories without conflict. So we combine them
            foreach ($cell_ids_by_rs as $rs => $rs_cell_id_array) {
                $all_cell_ids = array_merge($all_cell_ids, $rs_cell_id_array);
            }
        }
        Log::debug('Cell:cellsTSVFolder - Number of Cell IDs = ' . count($all_cell_ids));

        // Remove the cell specific filter fields from the UI.
        $orig_filters = $filters;
        unset($filters['cell_id_cell']);
        unset($filters['expression_study_method_cell']);
        unset($filters['virtual_pairing_cell']);
        unset($filters['property_expression']);
        unset($filters['value_expression']);
        unset($filters['antigen_reactivity']);
        unset($filters['antigen_source_species_reactivity']);
        unset($filters['peptide_sequence_aa_reactivity']);

        // If there were filters, and there are no cell ids, then we didn't
        // find anything that matched our criteria so there are no results.
        // If there are results (filters need to be applied) and we have some
        // cell_ids then we process the data.
        $response_list = [];
        $response_list_cells_summary = [];
        if ($num_filters > 0 && count($all_cell_ids) > 0) {
            // If there are cell ID filters, set them.
            $filters['cell_id_cell'] = $all_cell_ids;
        }

        // Get repertoire summary for the cells that meet the criteria.
        $response_list_cells_summary = RestService::data_summary($filters, $username, false, 'cell');

        // Get a few cells from each service
        $response_list = RestService::data_subset($filters, $response_list_cells_summary, 10, 'cell');

        // do extra cell summary request to get expected number of cells
        // for sanity check after download
        $expected_nb_cells_by_rs = self::expectedCellsByRestSevice($response_list);

        // get filtered list of repertoires ids
        $filtered_samples_by_rs = self::filteredSamplesByRestService($response_list);

        // if total expected nb cells is 0, immediately fail download
        $total_expected_nb_cells = 0;
        foreach ($expected_nb_cells_by_rs as $rs => $count) {
            $total_expected_nb_cells += $count;
        }
        if ($total_expected_nb_cells <= 0) {
            throw new \Exception('No cells to download');
        }

        // if total expected nb cells > download limit, immediately fail download
        $cells_download_limit = config('ireceptor.cells_download_limit');
        if ($total_expected_nb_cells > $cells_download_limit) {
            throw new \Exception('Trying to download to many cells: ' . $total_expected_nb_cells . ' > ' . $cells_download_limit);
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

        // Get a list of responses for the metadata.
        $metadata_response_list = RestService::sample_list_repertoire_data($filtered_samples_by_rs, $folder_path, $username);

        $cell_response_list = [];
        $expression_response_list = [];
        $reactivity_response_list = [];
        $sequence_response_list = [];

        if ($download_data) {
            // cell data
            $cell_response_list = RestService::cells_data($filters, $folder_path, $username, $expected_nb_cells_by_rs);

            // expression data
            $expression_response_list = RestService::expression_data_from_cell_ids($cell_ids_by_rs, $folder_path, $username, $response_list);

            // reactivity data
            $reactivity_response_list = RestService::reactivity_data_from_cell_ids($cell_ids_by_rs, $folder_path, $username, $response_list);

            // sequence data
            $sequence_response_list = RestService::sequences_data_from_cell_ids($cell_ids_by_rs, $folder_path, $username, $expected_nb_cells_by_rs);
        } else {
            Log::debug('Cell::cellTSVFolder - SKIPPING DOWNLOAD');
        }

        $file_stats = self::file_stats($cell_response_list, $expression_response_list, $reactivity_response_list, $metadata_response_list, $sequence_response_list, $expected_nb_cells_by_rs, $download_data);

        // if some files are incomplete, log it
        foreach ($file_stats as $t) {
            if ($t['nb_cells'] != $t['expected_nb_cells']) {
                $delta = ($t['expected_nb_cells'] - $t['nb_cells']);
                $str = 'expected ' . $t['expected_nb_cells'] . ' cells, got ' . $t['nb_cells'] . ' cells (difference=' . $delta . ' cells)';
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
        foreach ($cell_response_list as $response) {
            if ($response['status'] == 'error') {
                $failed_rs[] = $response['rs'];
                $is_download_incomplete = true;
            }
        }

        // did the sequence download fail for some services?
        foreach ($sequence_response_list as $response) {
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

        // did the expression query fail for some services?
        foreach ($expression_response_list as $response) {
            if ($response['status'] == 'error') {
                $failed_rs[] = $response['rs'];
                $is_download_incomplete = true;
            }
        }

        // did the reactivity query fail for some services?
        foreach ($reactivity_response_list as $response) {
            if ($response['status'] == 'error') {
                $failed_rs[] = $response['rs'];
                $is_download_incomplete = true;
            }
        }

        // are some files incomplete?
        $nb_cells_total = 0;
        $expected_nb_cells_total = 0;
        foreach ($file_stats as $t) {
            $nb_cells_total += $t['nb_cells'];
            $expected_nb_cells_total += $t['expected_nb_cells'];
        }
        if ($nb_cells_total < $expected_nb_cells_total) {
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
                foreach ($cell_response_list as $response) {
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
        $info_file_path = self::generate_info_file($folder_path, $url, $sample_filters, $orig_filters, $file_stats, $username, $now, $failed_rs);

        // generate manifest.json
        $manifest_file_path = Sequence::generate_manifest_file($folder_path, $url, $sample_filters, $filters, $file_stats, $username, $now, $failed_rs);

        $t = [];
        $t['base_path'] = $storage_folder;
        $t['base_name'] = $base_name;
        $t['folder_path'] = $folder_path;
        $t['response_list'] = $cell_response_list;
        $t['metadata_response_list'] = $metadata_response_list;
        $t['expression_response_list'] = $expression_response_list;
        $t['reactivity_response_list'] = $reactivity_response_list;
        $t['sequence_response_list'] = $sequence_response_list;
        $t['info_file_path'] = $info_file_path;
        $t['manifest_file_path'] = $manifest_file_path;
        $t['is_download_incomplete'] = $is_download_incomplete;
        $t['download_incomplete_info'] = $download_incomplete_info;
        $t['file_stats'] = $file_stats;

        return $t;
    }

    public static function cellsTSV($filters, $username, $url = '',
                                    $sample_filters = [], $download_data = true)
    {
        $t = self::cellsTSVFolder($filters, $username, $url, $sample_filters, $download_data);

        $base_path = $t['base_path'];
        $base_name = $t['base_name'];
        $folder_path = $t['folder_path'];
        $cell_response_list = $t['response_list'];
        $metadata_response_list = $t['metadata_response_list'];
        $expression_response_list = $t['expression_response_list'];
        $reactivity_response_list = $t['reactivity_response_list'];
        $sequence_response_list = $t['sequence_response_list'];
        $info_file_path = $t['info_file_path'];
        $manifest_file_path = $t['manifest_file_path'];
        $is_download_incomplete = $t['is_download_incomplete'];
        $download_incomplete_info = $t['download_incomplete_info'];
        $file_stats = $t['file_stats'];

        // zip files
        $zip_path = self::zip_files($folder_path, $cell_response_list, $expression_response_list, $reactivity_response_list, $metadata_response_list, $info_file_path, $sequence_response_list, $manifest_file_path, $download_data);

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

    // Returns an array with the following keys:
    // - rs_list
    // - rs_list_no_response
    // - summary
    // - total_filtered_samples
    // - total_filtered_repositories
    // - total_filtered_labs
    // - total_filtered_studies
    // - total_filtered_objects
    // - filtered_repositories
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
            // cell count for that sample
            if (isset($sample->ir_cell_count)) {
                $total_object_count += $sample->ir_cell_count;
            }

            // filtered cell count for that sample
            if (isset($sample->ir_filtered_cell_count)) {
                $nb_filtered_objects = $sample->ir_filtered_cell_count;
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

            // total cells
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
        $rs_data['study_tree_cell'] = $study_tree;

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
        // Set info file name
        $info_file = 'info.txt';

        // Initialize string
        $s = '';

        $s .= '<p><b>Metadata filters</b></p>' . "\n";
        if (count($sample_filters) == 0) {
            $s .= 'None' . "\n";
        }
        foreach ($sample_filters as $k => $v) {
            if (is_array($v)) {
                $v = implode(' or ', $v);
            }
            // use human-friendly filter name
            $s .= __('short.' . $k) . ': ' . $v . "</br>\n";
        }
        $s .= "</br>\n";

        $s .= '<p><b>Cell filters</b></p>' . "\n";
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

        if (count($filters) == 0) {
            $s .= 'None' . "</br>\n";
        }
        foreach ($filters as $k => $v) {
            if (is_array($v)) {
                $v = implode(' or ', $v);
            }
            // use human-friendly filter name
            $s .= __('short.' . $k) . ': ' . $v . "</br>\n";
        }
        $s .= "</br>\n";

        $s .= '<p><b>Data/Repository Summary</b></p>' . "\n";

        $nb_cells_total = 0;
        $expected_nb_cells_total = 0;
        foreach ($file_stats as $t) {
            $nb_cells_total += $t['nb_cells'];
            $expected_nb_cells_total += $t['expected_nb_cells'];
        }

        $is_download_incomplete = ($nb_cells_total < $expected_nb_cells_total);
        if ($is_download_incomplete) {
            $s .= 'GW-ERROR: some of the files appears to be incomplete:' . "</br>\n";
            $s .= 'Total: ' . $nb_cells_total . ' cells, but ' . $expected_nb_cells_total . ' were expected.' . "</br>\n";
        } else {
            $s .= 'Total: ' . $nb_cells_total . ' cells' . "</br>\n";
        }

        foreach ($file_stats as $t) {
            if ($is_download_incomplete && ($t['nb_cells'] < $t['expected_nb_cells'])) {
                $s .= 'GW-ERROR: ' . $t['name'] . ' (' . $t['size'] . '): ' . $t['nb_cells'] . ' cells (incomplete, expected ' . $t['expected_nb_cells'] . ' cells) (from ' . $t['rs_url'] . ')' . "</br>\n";
            } else {
                $s .= $t['name'] . ' (' . $t['size'] . '): ' . $t['nb_cells'] . ' cells (from ' . $t['rs_url'] . ')' . "</br>\n";
            }
        }

        if (! empty($failed_rs)) {
            $s .= 'GW-ERROR: some files are missing because an error occurred while downloading cells from these repositories:' . "</br>\n";
            foreach ($failed_rs as $rs) {
                $s .= 'GW-ERROR: ' . $rs->name . "</br>\n";
            }
        }
        $s .= "</br>\n";

        $s .= '<p><b>Source</b></p>' . "\n";
        $s .= $url . "</br>\n";
        $date_str_human = date('M j, Y', $now);
        $time_str_human = date('H:i T', $now);
        $s .= 'Downloaded by ' . $username . ' on ' . $date_str_human . ' at ' . $time_str_human . "</br>\n";

        $info_file_path = $folder_path . '/' . $info_file;
        file_put_contents($info_file_path, $s);

        return $info_file_path;
    }

    public static function generate_cell_id_list_by_data_processing_from_cell_list($cell_list_by_rs)
    {
        foreach ($cell_list_by_rs as $i => $response) {
            $data_processing_id_list = [];

            $cell_list = $response['cell_list'];
            foreach ($cell_list as $cell) {
                if (! isset($cell['data_processing_id']) || ! isset($cell['cell_id'])) {
                    continue;
                }

                $data_processing_id = $cell['data_processing_id'];
                if (! isset($data_processing_id_list[$data_processing_id])) {
                    $data_processing_id_list[$data_processing_id] = [];
                }

                $cell_id = $cell['cell_id'];
                $data_processing_id_list[$data_processing_id][] = $cell_id;
            }

            $cell_list_by_rs[$i]['data_processing_id_list'] = $data_processing_id_list;

            // don't need this anymore
            unset($cell_list_by_rs[$i]['cell_list']);
        }

        return $cell_list_by_rs;
    }

    public static function zip_files($folder_path, $cell_response_list, $expression_response_list, $reactivity_response_list, $metadata_response_list, $info_file_path, $sequence_response_list, $manifest_file_path, $download_data)
    {
        $zipPath = $folder_path . '.zip';
        Log::info('Cell::zip_files - Zip files to ' . $zipPath);
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE);

        // cell data
        foreach ($cell_response_list as $response) {
            if (isset($response['data']['file_path'])) {
                $file_path = $response['data']['file_path'];
                Log::debug('Cell::zip_files - Adding to ZIP: ' . $file_path);
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

                Log::debug('Cell::zip_files - Adding to ZIP: ' . $file_path);
                $zip->addFile($file_path, basename($file_path));
            }
        }

        // sequence data
        foreach ($sequence_response_list as $response) {
            if (isset($response['data']['file_path'])) {
                $file_path = $response['data']['file_path'];
                Log::debug('Cell::zip_files - Adding to ZIP: ' . $file_path);
                $zip->addFile($file_path, basename($file_path));
            }
        }

        // expression data
        foreach ($expression_response_list as $response) {
            if (isset($response['data']['file_path'])) {
                $file_path = $response['data']['file_path'];
                Log::debug('Cell::zip_files - Adding to ZIP: ' . $file_path);
                $zip->addFile($file_path, basename($file_path));
            }
        }

        // reactivity data
        foreach ($reactivity_response_list as $response) {
            if (isset($response['data']['file_path'])) {
                $file_path = $response['data']['file_path'];
                Log::debug('Cell::zip_files - Adding to ZIP: ' . $file_path);
                $zip->addFile($file_path, basename($file_path));
            }
        }

        // Info file
        $zip->addFile($info_file_path, basename($info_file_path));
        Log::debug('Cell::zip_files - Adding to ZIP: ' . $info_file_path);

        // AIRR-manifest.json
        $zip->addFile($manifest_file_path, basename($manifest_file_path));
        Log::debug('Cell::zip_files - Adding to ZIP: ' . $manifest_file_path);

        $zip->close();

        return $zipPath;
    }

    public static function delete_files($folder_path)
    {
        Log::debug('Cell::delete_files - Deleting downloaded files...');
        if (File::exists($folder_path)) {
            // Check to see if the path is within the file space of Laravel managed
            // storage. This is a paranoid check to make sure we don't remove everything
            // on the system 8-)
            if (str_contains($folder_path, storage_path())) {
                Log::debug('Cell::delete_files - Deleting folder of downloaded files: ' . $folder_path);
                exec(sprintf('rm -rf %s', escapeshellarg($folder_path)));
            } else {
                Log::error('Cell::delete_files - Could not delete folder ' . $folder_path);
                Log::error('Cell::delete_files - Folder is not in the Laravel storage area ' . storage_path());
            }
        }
    }

    public static function file_stats($cell_response_list, $expression_response_list, $reactivity_response_list, $metadata_response_list, $sequence_response_list, $expected_nb_cells_by_rs, $download_data)
    {
        Log::debug('Cell::file_stats - Get files stats');
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
                    // cell file name
                    $t['cell_file_name'] = $t['name'];

                    // repertoire file name
                    foreach ($metadata_response_list as $r) {
                        if ($rest_service_id == $r['rs']->id) {
                            if (isset($r['data']['file_path'])) {
                                $repertoire_file_path = $r['data']['file_path'];
                                $t['metadata_file_name'] = basename($repertoire_file_path);
                            }
                        }
                    }

                    // expression file name
                    foreach ($expression_response_list as $r) {
                        if ($rest_service_id == $r['rs']->id) {
                            if (isset($r['data']['file_path'])) {
                                $expression_file_path = $r['data']['file_path'];
                                $t['expression_file_name'] = basename($expression_file_path);
                            }
                        }
                    }

                    // reactivity file name
                    foreach ($reactivity_response_list as $r) {
                        if ($rest_service_id == $r['rs']->id) {
                            if (isset($r['data']['file_path'])) {
                                $reactivity_file_path = $r['data']['file_path'];
                                $t['reactivity_file_name'] = basename($reactivity_file_path);
                            }
                        }
                    }

                    // sequence file name
                    foreach ($sequence_response_list as $r) {
                        if ($rest_service_id == $r['rs']->id) {
                            if (isset($r['data']['file_path'])) {
                                $sequence_file_path = $r['data']['file_path'];
                                $t['sequence_file_name'] = basename($sequence_file_path);
                            }
                        }
                    }

                    // Get the associated sequence file
                    foreach ($sequence_response_list as $sequence_response) {
                        // If the service IDs match, then the files match
                        if ($rest_service_id == $sequence_response['rs']->id) {
                            // If there is a file path, keep track of the metadata file
                            if (isset($sequence_response['data']['file_path'])) {
                                $sequence_file_path = $sequence_response['data']['file_path'];
                                $t['rearrangement_name'] = basename($sequence_file_path);
                            }
                        }
                    }

                    // Count number of times the AIRR cell_id field occurs in the file.
                    // This is the number of cells returned by the query.
                    $n = 0;
                    $f = fopen($file_path, 'r');
                    while (! feof($f)) {
                        $line = fgets($f);
                        if (! empty($line)) {
                            $n += substr_count($line, '"cell_id"');
                        }
                    }
                    fclose($f);
                    $t['nb_cells'] = $n;

                    $t['expected_nb_cells'] = 0;
                    if (isset($expected_nb_cells_by_rs[$rest_service_id])) {
                        $t['expected_nb_cells'] = $expected_nb_cells_by_rs[$rest_service_id];
                    } else {
                        Log::error('rest_service ' . $rest_service_id . ' is missing from $expected_nb_cells_by_rs array');
                        Log::error($expected_nb_cells_by_rs);
                    }
                } else {
                    // The metadata/repertoire file name is just the name
                    $t['metadata_file_name'] = $t['name'];

                    // If we aren't downloading we expect and got 0 cells
                    $t['nb_cells'] = 0;
                    $t['expected_nb_cells'] = 0;
                }
                $t['query_log_id'] = $response['query_log_id'];
                $t['rest_service_name'] = $response['rs']->name;
                $t['incomplete'] = ($t['nb_cells'] != $t['expected_nb_cells']);

                $file_stats[] = $t;
            }
        }

        return $file_stats;
    }
}
