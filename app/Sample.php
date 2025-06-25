<?php

namespace App;

use Carbon\Carbon;
use Facades\App\CloneCount;
use Facades\App\FieldName;
use Facades\App\RestService;
use Illuminate\Support\Facades\Log;

class Sample
{
    public static function public_samples($sample_type = 'sequence')
    {
        return CachedSample::cached($sample_type);
    }

    public static function metadata()
    {
        return CachedSample::metadata();
    }

    public static function sort_rest_service_list($rs_list)
    {
        // sort rest services alphabetically
        usort($rs_list, function ($a, $b) {
            $a_name = isset($a['rs_name']) ? $a['rs_name'] : $a['rs']->display_name;
            $b_name = isset($b['rs_name']) ? $b['rs_name'] : $b['rs']->display_name;

            return strcasecmp($a_name, $b_name);
        });

        // sort labs and studies alphabetically
        $rs_list_sorted = [];
        foreach ($rs_list as $rs) {
            $lab_list = $rs['study_tree'];

            // sort labs
            usort($lab_list, function ($a, $b) {
                $a_name = $a['name'];
                $b_name = $b['name'];

                return strcasecmp($a_name, $b_name);
            });

            // sort studies
            $lab_list_sorted = [];
            foreach ($lab_list as $lab) {
                $studies_sorted = [];
                usort($lab['studies'], function ($a, $b) {
                    $a_name = $a['study_title'];
                    $b_name = $b['study_title'];

                    return strcasecmp($a_name, $b_name);
                });
                $studies_sorted[] = $lab['studies'];
                $lab_list_sorted[] = $lab;
            }

            $rs['study_tree'] = $lab_list_sorted;
            $rs_list_sorted[] = $rs;
        }

        return $rs_list_sorted;
    }

    public static function cache_sequence_counts($username, $rest_service_id = null)
    {
        $response_list = RestService::samples([], $username, false, [$rest_service_id]);
        foreach ($response_list as $i => $response) {
            $rest_service_id = $response['rs']->id;
            $sample_list = $response['data'];

            $t = [];
            $t['rest_service_id'] = $rest_service_id;
            $t['sequence_counts'] = [];

            $total_sequence_count = 0;
            foreach ($sample_list as $sample) {
                $sample_id = $sample->repertoire_id;
                //$sequence_count_array = RestService::sequence_count([$rest_service_id => [$sample_id]], [], false);
                $sequence_count_array = RestService::object_count('sequence', [$rest_service_id => [$sample_id]], [], false);
                $sequence_count = $sequence_count_array[$rest_service_id]['samples'][$sample_id];
                $t['sequence_counts'][$sample_id] = $sequence_count;
                $total_sequence_count += $sequence_count;
                Log::debug('Total sequence count = ' . $total_sequence_count);

                // HACK: to avoid hitting throttling limits
                sleep(1);
            }

            SequenceCount::create($t);

            // cache total counts
            $rs = RestService::find($rest_service_id);
            $rs->nb_samples = count($sample_list);
            $rs->nb_sequences = $total_sequence_count;
            $rs->last_cached = new Carbon('now');
            $rs->save();
        }
    }

    public static function cache_clone_counts($username, $rest_service_id = null)
    {
        $response_list = RestService::samples([], $username, false, [$rest_service_id]);
        foreach ($response_list as $i => $response) {
            $rest_service_id = $response['rs']->id;
            $sample_list = $response['data'];

            $t = [];
            $t['rest_service_id'] = $rest_service_id;
            $t['clone_counts'] = [];

            $total_clone_count = 0;
            foreach ($sample_list as $sample) {
                $sample_id = $sample->repertoire_id;
                //$clone_count_array = RestService::clone_count([$rest_service_id => [$sample_id]], [], false);
                $clone_count_array = RestService::object_count('clone', [$rest_service_id => [$sample_id]], [], false);
                $clone_count = $clone_count_array[$rest_service_id]['samples'][$sample_id];
                $t['clone_counts'][$sample_id] = $clone_count;
                $total_clone_count += $clone_count;
                Log::debug('Total clone count = ' . $total_clone_count);

                // HACK: to avoid hitting throttling limits
                sleep(1);
            }

            CloneCount::create($t);

            // cache total counts
            $rs = RestService::find($rest_service_id);
            $rs->nb_samples = count($sample_list);
            $rs->nb_clones = $total_clone_count;
            $rs->last_cached = new Carbon('now');
            $rs->save();
        }
    }

    public static function cache_cell_counts($username, $rest_service_id = null)
    {
        $response_list = RestService::samples([], $username, false, [$rest_service_id]);
        foreach ($response_list as $i => $response) {
            $rest_service_id = $response['rs']->id;
            $sample_list = $response['data'];

            $t = [];
            $t['rest_service_id'] = $rest_service_id;
            $t['cell_counts'] = [];

            $total_cell_count = 0;
            foreach ($sample_list as $sample) {
                $sample_id = $sample->repertoire_id;
                //$cell_count_array = RestService::cell_count([$rest_service_id => [$sample_id]], [], false);
                $cell_count_array = RestService::object_count('cell', [$rest_service_id => [$sample_id]], [], false);
                $cell_count = $cell_count_array[$rest_service_id]['samples'][$sample_id];
                $t['cell_counts'][$sample_id] = $cell_count;
                $total_cell_count += $cell_count;
                Log::debug('Total cell count = ' . $total_cell_count);

                // HACK: to avoid hitting throttling limits
                sleep(1);
            }

            CellCount::create($t);

            // cache total counts
            $rs = RestService::find($rest_service_id);
            $rs->nb_samples = count($sample_list);
            $rs->nb_cells = $total_cell_count;
            $rs->last_cached = new Carbon('now');
            $rs->save();
        }
    }

    public static function cache_epitope_counts($username, $rest_service_id = null)
    {
        $response_list = RestService::samples([], $username, false, [$rest_service_id]);
        foreach ($response_list as $i => $response) {
            $rest_service_id = $response['rs']->id;
            $sample_list = $response['data'];
            // Get a list of repertoire_ids
            $repertoire_list = [];
            foreach ($sample_list as $sample) {
                array_push($repertoire_list, $sample->repertoire_id);
            }
            //
            // Get a list of unique ir_species_ref values.
            $species_array = RestService::object_list('sequence', [$rest_service_id => $repertoire_list], [], 'ir_species_ref');
            Log::debug('species array = ' . json_encode($species_array[$rest_service_id]));
            foreach ($species_array[$rest_service_id] as $species_list) {
                if ($species_list != null) {
                    foreach ($species_list as $species_id) {
                        Log::debug('species = ' . $species_id);
                        $t = [];
                        $t['species_id'] = $species_id;
                        $t['species_name'] = 'test';
                        $existing_species = Species::where('species_id', $species_id)->take(1)->get();
                        Log::debug('existing species = ' . $existing_species);
                        if (count($existing_species) == 0) {
                            Species::create($t);
                        }
                    }
                }
            }
            // Get a list of unique ir_antigen_ref values.
            $antigen_array = RestService::object_list('sequence', [$rest_service_id => $repertoire_list], [], 'ir_antigen_ref');
            Log::debug('antigen array = ' . json_encode($antigen_array[$rest_service_id]));
            foreach ($antigen_array[$rest_service_id] as $antigen_list) {
                if ($antigen_list != null) {
                    foreach ($antigen_list as $antigen_id) {
                        Log::debug('antigen = ' . $antigen_id);
                        $t = [];
                        $t['antigen_id'] = $antigen_id;
                        $t['antigen_name'] = 'test';
                        $existing_antigen = Antigens::where('antigen_id', $antigen_id)->take(1)->get();
                        Log::debug('existing antigen = ' . $existing_antigen);
                        if (count($existing_antigen) == 0) {
                            Antigens::create($t);
                        }
                    }
                }
            }

            $epitope_data = [];
            /*
            $total_cell_count = 0;
            foreach ($sample_list as $sample) {
                $sample_id = $sample->repertoire_id;
                //$cell_count_array = RestService::cell_count([$rest_service_id => [$sample_id]], [], false);
                $cell_count_array = RestService::object_count('cell', [$rest_service_id => [$sample_id]], [], false);
                $cell_count = $cell_count_array[$rest_service_id]['samples'][$sample_id];
                $t['cell_counts'][$sample_id] = $cell_count;
                $total_cell_count += $cell_count;
                Log::debug('Total cell count = ' . $total_cell_count);

                // HACK: to avoid hitting throttling limits
                sleep(1);
            }
             */

            // cache total counts
            /*
            $rs = RestService::find($rest_service_id);
            $rs->nb_samples = count($sample_list);
            $rs->nb_cells = $total_cell_count;
            $rs->last_cached = new Carbon('now');
            $rs->save();
             */
        }
    }

    public static function find_sample_id_list($filters, $username)
    {
        // get samples
        $sample_data = self::find($filters, $username, false);
        $sample_list = $sample_data['items'];

        // generate list of sample ids
        $sample_id_list = [];
        foreach ($sample_list as $sample) {
            $sample_id_list['ir_project_sample_id_list_' . $sample->real_rest_service_id][] = $sample->repertoire_id;
        }

        return $sample_id_list;
    }

    /**
     * Find samples matching the filters.
     *
     * @param  $raw  - if true, don't convert fields
     */
    public static function find($filters, $username, $count_sequences = true, $type = '', $raw = false)
    {
        $service_filters = $filters;

        // rest service filter
        $rest_service_id_list = null;
        if (isset($service_filters['rest_service_name'])) {
            $str = $service_filters['rest_service_name'];
            $rest_service_id_list = [];

            foreach (RestService::findEnabled() as $rs) {
                $name = $rs->display_name;
                if (stripos($name, $str) !== false) {
                    $rest_service_id_list[] = $rs->id;
                }
            }
            unset($service_filters['rest_service_name']);
        }

        // do requests
        $response_list = RestService::samples($service_filters, $username, $count_sequences, $rest_service_id_list);

        // if error, update gateway query status
        $gw_query_log_id = request()->get('query_log_id');
        foreach ($response_list as $response) {
            if ($response['status'] == 'error') {
                QueryLog::set_gateway_query_status($gw_query_log_id, 'service_error', $response['error_message']);
            }
        }

        // tweak responses
        $sample_list_all = [];
        $nb_samples_with_sequences = 0;
        $nb_samples_with_clones = 0;
        $nb_samples_with_cells = 0;
        foreach ($response_list as $i => $response) {
            $rs = $response['rs'];
            $sample_list = $response['data'];

            // do full text search if required
            if (isset($filters['full_text_search'])) {
                $search_terms = explode(' ', $filters['full_text_search']);

                $sample_list_result = [];
                foreach ($sample_list as $sample) {
                    $sample_str = json_encode($sample);
                    $is_match = true;
                    foreach ($search_terms as $term) {
                        $term = trim($term);
                        if (stripos($sample_str, $term) === false) {
                            $is_match = false;
                        }
                    }
                    if ($is_match) {
                        $sample_list_result[] = $sample;
                    }
                }

                $response_list[$i]['data'] = $sample_list_result;
                $sample_list = $sample_list_result;
            }

            // filter by sequence count if needed
            if (isset($filters['ir_sequence_count'])) {
                $sample_list_result = [];

                $extra_characters = [',', ' '];
                $sequence_count_filter_str = str_replace($extra_characters, '', $filters['ir_sequence_count']);
                if (is_numeric($sequence_count_filter_str)) {
                    $sequence_count_filter = intval($sequence_count_filter_str);

                    foreach ($sample_list as $sample) {
                        if ($sample->ir_sequence_count == $sequence_count_filter) {
                            $sample_list_result[] = $sample;
                        }
                    }
                }

                $response_list[$i]['data'] = $sample_list_result;
                $sample_list = $sample_list_result;
            }

            // break up repertoires based on type (if it has sequences, clones, cells)
            $samples_with_sequences = [];
            $samples_with_clones = [];
            $samples_with_cells = [];

            foreach ($sample_list as $sample) {
                if (isset($sample->ir_cell_count) && $sample->ir_cell_count > 0) {
                    $samples_with_cells[] = $sample;
                } elseif (isset($sample->ir_clone_count) && $sample->ir_clone_count > 0) {
                    $samples_with_clones[] = $sample;
                } else {
                    $samples_with_sequences[] = $sample;
                }
            }

            $nb_samples_with_sequences += count($samples_with_sequences);
            $nb_samples_with_clones += count($samples_with_clones);
            $nb_samples_with_cells += count($samples_with_cells);

            $sample_list_result = $samples_with_sequences;
            if ($type == 'clone') {
                $sample_list_result = $samples_with_clones;
            } elseif ($type == 'cell') {
                $sample_list_result = $samples_with_cells;
            }

            $response_list[$i]['data'] = $sample_list_result;
            $sample_list = $sample_list_result;

            if (! $raw) {
                $sample_list = self::convert_sample_list($sample_list, $rs);
            }

            $sample_list_all = array_merge($sample_list_all, $sample_list);
        }

        // build list of services which didn't respond
        $rs_list_no_response = [];
        foreach ($response_list as $i => $response) {
            $rs = $response['rs'];

            if ($response['status'] == 'error') {
                $rs->error_type = 'error';
                if (isset($response['error_type'])) {
                    $rs->error_type = $response['error_type'];
                }
                $rs_list_no_response[] = $rs;
            }
        }

        // build list of services which didn't return the sequence counts
        $rs_list_sequence_count_error = [];
        foreach ($response_list as $i => $response) {
            $rs = $response['rs'];

            if (isset($response['sequence_count_error'])) {
                $rs_list_sequence_count_error[] = $rs;
            }
        }

        // return the statistics about that list of samples
        $count_field = 'ir_sequence_count';
        if ($type == 'clone') {
            $count_field = 'ir_clone_count';
        } elseif ($type == 'cell') {
            $count_field = 'ir_cell_count';
        }

        if ($raw) {
            $data['items'] = $sample_list_all;
        } else {
            $data = self::stats($sample_list_all, $count_field);
        }

        $data['rs_list_no_response'] = $rs_list_no_response;
        $data['rs_list_sequence_count_error'] = $rs_list_sequence_count_error;
        $data['nb_samples_with_sequences'] = $nb_samples_with_sequences;
        $data['nb_samples_with_clones'] = $nb_samples_with_clones;
        $data['nb_samples_with_cells'] = $nb_samples_with_cells;

        return $data;
    }

    // Recursive function to flatten out all of the leaf nodes in a JSON object based on the
    // path provided. Helps us to create a list of leaf node strings from a complex object so
    // we can display those strings as a list of strings.
    public static function airr_flatten($object, $path)
    {
        $field_value = '';
        // If we have a path that is . separated, process it.
        $split_loc = strpos($path, '.');
        if ($split_loc) {
            // Split the path into an array
            $field_array = explode('.', $path);
            $current_field = $field_array[0];
            $target_field = $field_array[count($field_array) - 1];

            // If the property we are processing currently exists, process it.
            if (property_exists($object, $current_field)) {
                // Get the new path and the new object.
                $new_path = substr($path, $split_loc + 1);
                $new_object = data_get($object, $current_field);
                // If the object is an array, loop over the array elements.
                if (is_array($new_object)) {
                    foreach ($new_object as $array_element) {
                        // Flatten out the sub objects elements.
                        $new_value = Sample::airr_flatten($array_element, $new_path);
                        // Build the new array of return values. If we currently have an empty
                        // field, just use the new value, if we have data already, then add the
                        // new data to the old, separated by a comma.
                        if (strlen($field_value) == 0) {
                            $field_value = $new_value;
                        } elseif (strlen($new_value) > 1) {
                            $field_value = $field_value . ', ' . $new_value;
                        }
                    }
                } else {
                    // Flatten out the new object
                    $new_value = Sample::airr_flatten($new_object, $new_path);
                    // Build the new array of return values. If we currently have an empty
                    // field, just use the new value, if we have data already, then add the
                    // new data to the old, separated by a comma.
                    if (strlen($field_value) == 0) {
                        $field_value = $new_value;
                    } elseif (strlen($new_value) > 1) {
                        $field_value = $field_value . ', ' . $new_value;
                    }
                }
            }
        } else {
            // If we are here, we are at the end of the path, so just get the data element.
            $current_field = $path;
            $field_value = trim(data_get($object, $current_field));
        }

        // Return the value.
        return $field_value;
    }

    // convert/complete sample list
    public static function convert_sample_list($sample_list, $rs)
    {
        $sample_field_list = FieldName::getSampleFields($rs->api_version);

        $new_sample_list = [];
        // Iterate over the samples
        foreach ($sample_list as $sample) {
            $new_sample = new \stdClass();

            // For each sample, iterate over the fields.
            foreach ($sample_field_list as $sample_field) {
                if (isset($sample_field['ir_adc_api_response'])) {
                    $field_name = $sample_field['ir_id'];
                    // Set the value of the field in our new sample object. We flatten
                    // out the field values if the field is an "Object" field. This is denoted
                    // by having a hyphen '-' splitting the subobjects in the ir_id field of the
                    // AIRR Mapping file. This is required for fields like 'genotype' which need
                    // to be processed as JSON objects.
                    if (str_contains($sample_field['ir_id'], '-')) {
                        //Log::debug('Sample::convert_sample_list - calling airr_flatten for ' . $sample_field['ir_id']);
                        $new_sample->{$field_name} = Sample::airr_flatten($sample, $sample_field['ir_adc_api_query']);
                    } else {
                        $field_value = data_get($sample, $sample_field['ir_adc_api_response']);
                        $new_sample->{$field_name} = $field_value;
                    }
                }
            }

            // add extra fields (not defined in mapping file)
            $fields = ['repertoire_id', 'real_rest_service_id', 'ir_sequence_count', 'ir_clone_count', 'ir_cell_count', 'ir_filtered_sequence_count', 'ir_filtered_clone_count', 'ir_filtered_cell_count', 'stats'];
            foreach ($fields as $field_name) {
                if (isset($sample->{$field_name})) {
                    $new_sample->{$field_name} = $sample->{$field_name};
                }
            }

            // add rest service id/name
            $new_sample->rest_service_id = $rs->id;
            $new_sample->rest_service_name = $rs->display_name;
            $new_sample->rest_service_group_code = $rs->rest_service_group_code ?? null;

            // add study URL
            $new_sample = self::generate_study_urls($new_sample);

            $new_sample_list[] = $new_sample;
        }

        return $new_sample_list;
    }

    public static function stats($sample_list, $count_field = 'ir_sequence_count')
    {
        // group samples by service
        $samples_by_rs = [];
        foreach ($sample_list as $sample) {
            $rs_id = $sample->rest_service_id;
            $rs_group_code = $sample->rest_service_group_code;

            // Initialize if first time we have seen this rs_id
            if (! isset($samples_by_rs[$rs_id])) {
                $samples_by_rs[$rs_id]['name'] = $sample->rest_service_name;
                $samples_by_rs[$rs_id]['rs_group_code'] = $rs_group_code;
                $samples_by_rs[$rs_id]['sample_list'] = [];
            }

            // Add the sample to the sample_list for the rest service ID (rs_id)
            $samples_by_rs[$rs_id]['sample_list'][] = $sample;
        }

        // Example:
        // [
        //   14 =>
        //   [
        //     'name' => 'IPA 1',
        //     'sample_list' => [...],
        //   ],
        //   15 =>
        //   [
        //     'name' => 'IPA 2',
        //     'sample_list' => [...],
        //   ],
        // ]

        // initialize return array
        $data = [];
        $data['items'] = [];
        $data['rs_list'] = [];
        $data['total'] = 0;

        foreach ($samples_by_rs as $rs_id => $rs_sample_info) {
            $rs_name = $rs_sample_info['name'];
            $rs_group_code = $rs_sample_info['rs_group_code'];
            $rs_sample_list = $rs_sample_info['sample_list'];

            // calculate summary statistics
            $lab_list = [];
            $study_list = [];
            // We count objects, either sequences, clones, or cells.
            $lab_object_count = [];
            $study_object_count = [];
            $total_object_count = 0;

            foreach ($rs_sample_list as $sample) {
                $object_count = 0;
                if (isset($sample->{$count_field}) && is_numeric($sample->{$count_field})) {
                    $object_count = $sample->{$count_field};
                }

                if (isset($sample->lab_name)) {
                    if (! in_array($sample->lab_name, $lab_list)) {
                        $lab_list[] = $sample->lab_name;
                        $lab_object_count[$sample->lab_name] = $object_count;
                    } else {
                        $lab_object_count[$sample->lab_name] += $object_count;
                    }
                } elseif (isset($sample->collected_by)) {
                    if (! in_array($sample->collected_by, $lab_list)) {
                        $lab_list[] = $sample->collected_by;
                    }
                }

                if (! isset($sample->study_title)) {
                    $sample->study_title = '';
                }

                if (! in_array($sample->study_title, $study_list)) {
                    $study_list[] = $sample->study_title;
                    $study_object_count[$sample->study_title] = $object_count;
                } else {
                    $study_object_count[$sample->study_title] += $object_count;
                }

                $total_object_count += $object_count;
            }

            $study_tree = [];
            foreach ($rs_sample_list as $sample) {
                // sample has no lab_name.
                if (isset($sample->lab_name)) {
                    $lab = $sample->lab_name;
                } else {
                    $lab = 'UNKNOWN';
                }

                // If we don't have this lab already, create it.
                if (! isset($study_tree[$lab])) {
                    $lab_data['name'] = $lab;
                    if (isset($lab_object_count[$lab])) {
                        $lab_data['total_object_count'] = $lab_object_count[$lab];
                    } else {
                        $lab_data['total_object_count'] = 0;
                    }
                    $study_tree[$lab] = $lab_data;
                }

                // Check to see if the study exists in the lab, and if not, create it.
                if (! isset($study_tree[$lab]['studies'])) {
                    $new_study_data['study_title'] = $sample->study_title;
                    if (isset($study_object_count[$sample->study_title])) {
                        $new_study_data['total_object_count'] = $study_object_count[$sample->study_title];
                    } else {
                        $new_study_data['total_object_count'] = 0;
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
                        if (isset($study_object_count[$sample->study_title])) {
                            $new_study_data['total_object_count'] = $study_object_count[$sample->study_title];
                        } else {
                            $new_study_data['total_object_count'] = 0;
                        }
                        $study_tree[$lab]['studies'][$sample->study_title] = $new_study_data;
                    }
                }
            }

            // rest service data
            $rs_data = [];
            $rs_data['rs_name'] = $rs_name;
            $rs_data['rs_group_code'] = $rs_group_code;
            $rs_data['rs_id'] = $rs_id;
            $rs_data['study_tree'] = $study_tree;
            $rs_data['total_samples'] = count($rs_sample_list);
            $rs_data['total_labs'] = count($lab_list);
            $rs_data['total_studies'] = count($study_list);
            $rs_data['total_object_count'] = $total_object_count;
            $rs_data['total_filtered_objects'] = $total_object_count;
            $data['rs_list'][] = $rs_data;

            // sample data
            $data['total'] += $rs_data['total_samples'];
            $data['items'] = array_merge($rs_sample_list, $data['items']);
        }

        // aggregate summary statistics
        $total_filtered_repositories = 0;
        $total_filtered_labs = 0;
        $total_filtered_studies = 0;
        $total_filtered_samples = 0;
        $total_filtered_objects = 0;

        foreach ($data['rs_list'] as $rs_data) {
            if ($rs_data['total_samples'] > 0) {
                $total_filtered_repositories++;
            }

            $total_filtered_samples += $rs_data['total_samples'];
            $total_filtered_labs += $rs_data['total_labs'];
            $total_filtered_studies += $rs_data['total_studies'];
            $total_filtered_objects += $rs_data['total_object_count'];
        }

        // sort alphabetically repositories/labs/studies
        $data['rs_list'] = self::sort_rest_service_list($data['rs_list']);

        $data['total_filtered_samples'] = $total_filtered_samples;
        $data['total_filtered_repositories'] = $total_filtered_repositories;
        $data['total_filtered_labs'] = $total_filtered_labs;
        $data['total_filtered_studies'] = $total_filtered_studies;
        $data['total_filtered_objects'] = $total_filtered_objects;

        return $data;
    }

    public static function generate_study_urls($sample)
    {
        // generate NCBI url
        if (isset($sample->study_id)) {
            if (preg_match('/PRJ/', $sample->study_id)) {
                $sample->ncbi_url = 'https://www.ncbi.nlm.nih.gov/bioproject/?term=' . $sample->study_id;
            } elseif (preg_match('/SRP/', $sample->study_id)) {
                $sample->ncbi_url = 'https://www.ncbi.nlm.nih.gov/Traces/sra/?study=' . $sample->study_id;
            } else {
            }
        }

        // generate study URL
        if (isset($sample->pub_ids)) {
            if (! (stripos($sample->pub_ids, 'PMID') === false)) {
                // remove any character which is not a digit
                $pmid = preg_replace('~\D~', '', $sample->pub_ids);
                $sample->study_url = 'https://www.ncbi.nlm.nih.gov/pubmed/' . $pmid;
            } elseif (! (stripos($sample->pub_ids, 'DOI') === false)) {
                $doi = str_replace('DOI: ', '', $sample->pub_ids);
                $sample->study_url = 'http://doi.org/' . $doi;
            } elseif (is_url($sample->pub_ids)) {
                $sample->study_url = $sample->pub_ids;
            }
        }

        return $sample;
    }

    public static function samplesJSON($filters, $username)
    {
        // get samples
        $sample_data = self::find($filters, $username, true, '', true);
        $sample_list = $sample_data['items'];

        // build JSON structure
        $obj = new \stdClass();

        $obj->Info = new \stdClass();
        $obj->Info->title = 'AIRR Data Commons API';
        $obj->Info->description = 'API response for repertoire query';
        $obj->Info->version = 1.3;
        $obj->Info->contact = new \stdClass();
        $obj->Info->contact->name = 'AIRR Community';
        $obj->Info->contact->url = 'https://github.com/airr-community';

        $sample_field_list = FieldName::getSampleFields();
        $obj->Repertoire = [];
        foreach ($sample_list as $sample) {
            $obj->Repertoire[] = $sample;
        }

        // generate JSON string from JSON structure
        $json = json_encode($obj, JSON_PRETTY_PRINT);

        // Full path of receiving folder for the download data, based on the
        // download_data_folder config variable, which is relative to the
        // Laravel storage_path().
        $storage_folder = storage_path() . '/' . config('ireceptor.downloads_data_folder') . '/';
        //$storage_folder = storage_path() . '/app/public/';

        $now = time();
        $time_str = date('Y-m-d_Hi', $now);
        $file_name = 'ir_' . $time_str . '_' . uniqid() . '.json';
        $file_path = $storage_folder . $file_name;

        // write JSON string to file
        file_put_contents($file_path, $json);

        //$public_path = 'storage' . str_after($file_path, storage_path('app/public'));

        $t = [];
        $t['size'] = filesize($file_path);
        $t['system_path'] = $file_path;
        //$t['public_path'] = $public_path;

        return $t;
    }

    public static function samplesTSV($filters, $username)
    {
        // get samples
        $sample_data = self::find($filters, $username);
        $sample_list = $sample_data['items'];

        // get sample fields
        $field_list = FieldName::getSampleFields();

        $columns = [];
        foreach ($field_list as $field) {
            $column_name = $field['airr'];
            if ($column_name == '') {
                $column_name = snake_case($field['ir_short']);
            }
            $columns[] = $column_name;
        }

        // Full path of receiving folder for the download data, based on the
        // download_data_folder config variable, which is relative to the
        // Laravel storage_path().
        $storage_folder = storage_path() . '/' . config('ireceptor.downloads_data_folder') . '/';
        //$storage_folder = storage_path() . '/app/public/';

        $now = time();
        $time_str = date('Y-m-d_Hi', $now);
        $file_name = 'ir_' . $time_str . '_' . uniqid() . '.tsv';
        $file_path = $storage_folder . $file_name;

        // create file
        $f = fopen($file_path, 'w');

        // write headers row
        fputcsv($f, $columns, "\t");

        // write contents
        foreach ($sample_list as $sample) {
            $row_columns = [];
            foreach ($field_list as $field) {
                $value = ' ';
                if (isset($sample->{$field['ir_id']})) {
                    $value = $sample->{$field['ir_id']};

                    // if field is boolean
                    if (isset($field['airr_type']) && ($field['airr_type'] == 'boolean')) {
                        if ($value) {
                            $value = 'T';
                        } else {
                            $value = 'F';
                        }
                    }

                    // if value is object/array
                    elseif (is_object($value) || is_array($value)) {
                        $value = json_encode($value);
                    }

                    // remove any line breaks
                    $value = str_replace(["\r", "\n"], ' ', $value);
                }
                $row_columns[] = $value;
            }
            fputcsv($f, $row_columns, "\t");
        }

        //$public_path = 'storage' . str_after($file_path, storage_path('app/public'));

        $t = [];
        $t['size'] = filesize($file_path);
        $t['system_path'] = $file_path;
        //$t['public_path'] = $public_path;

        return $t;
    }

    public static function sort_sample_list($sample_list, $sort_column, $sort_order)
    {
        $field_type = FieldName::getFieldType($sort_column);
        if ($sort_column == 'ir_sequence_count' || $sort_column == 'ir_clone_count' || $sort_column == 'ir_cell_count') {
            $field_type = 'integer';
        }

        usort($sample_list, function ($a, $b) use ($sort_column, $sort_order, $field_type) {
            $comparison_result = 0;

            if ($sort_column == 'ir_sequence_count' || $sort_column == 'ir_clone_count' || $sort_column == 'ir_cell_count') {
                if (! isset($a->{$sort_column})) {
                    $a->{$sort_column} = 0;
                }
                if (! isset($b->{$sort_column})) {
                    $b->{$sort_column} = 0;
                }
            }

            if (! isset($a->{$sort_column}) && (! isset($b->{$sort_column}))) {
                // If they are both not set they are equal
                $comparison_result = 0;
            } elseif (! isset($a->{$sort_column})) {
                // If a is not set and b is, then a < b
                $comparison_result = -1;
            } elseif (! isset($b->{$sort_column})) {
                // If b is not set then a > b
                $comparison_result = 1;
            } else {
                // If we get here, both are set.
                $val1 = $a->{$sort_column};
                $val2 = $b->{$sort_column};

                if (is_array($val1) || is_object($val1) || is_array($val2) || is_object($val2)) {
                    $val1 = json_encode($val1);
                    $val2 = json_encode($val2);
                    $comparison_result = strcasecmp($val1, $val2);
                } elseif ($field_type == 'integer' || $field_type == 'number') {
                    if ($val1 == $val2) {
                        $comparison_result = 0;
                    } elseif ($val1 < $val2) {
                        $comparison_result = -1;
                    } else {
                        $comparison_result = 1;
                    }
                } else {
                    $comparison_result = strcasecmp($val1, $val2);
                }
            }

            if ($sort_order == 'desc') {
                $comparison_result = -1 * $comparison_result;
            }

            return $comparison_result;
        });

        return $sample_list;
    }

    public static function generateChartData($sample_list, $stat_field, $label_field, $count_field = 'ir_sequence_count')
    {
        // Keep track of the counts for each field value
        $valuesCounts = [];
        // Keep track of the label to be used for each field value
        $valuesLabels = [];
        $valuesLabels['None'] = 'None';

        //Log::debug('generateChartData: field = ' . $count_field);
        // Iterate over each sample
        foreach ($sample_list as $sample) {
            $sample = json_decode(json_encode($sample), true);
            //Log::debug('Sample = ' . $sample['sample_id']);

            // Get the number of sequences for that sample
            $nb_sequences = 0;
            if (isset($sample[$count_field])) {
                $nb_sequences = $sample[$count_field];
                if ($nb_sequences > 0 && $count_field == 'ir_cell_count') {
                    //Log::debug('Sample = ' . $sample['sample_id'] . ' = ' . $nb_sequences);
                }
            }

            if (isset($sample[$stat_field]) && $sample[$stat_field] != null) {
                // If the field has a non-null value, get the value for the field for this sample
                $value = $sample[$stat_field];
                // If our array of counts has not seen this field value yet, initialize the
                // count to 0. If we have seen this field value, then this array element
                // already has a value
                if (! isset($valuesCounts[$value])) {
                    $valuesCounts[$value] = 0;
                }
                // Increment the total count for this value by the number of sequences.
                $valuesCounts[$value] += $nb_sequences;

                // Get a label mapping for this field value.
                if (! isset($valuesLabels[$value])) {
                    if (isset($sample[$label_field]) && $sample[$label_field] != null) {
                        $valuesLabels[$value] = $sample[$label_field];
                    }
                }
            } else {
                // If the field doesn't exist in this sample, we still want to keep
                // track of the number of sequence where there was no value for the field.
                // We use the "None" tag for this.
                if (! isset($valuesCounts['None'])) {
                    $valuesCounts['None'] = 0;
                }
                $valuesCounts['None'] += $nb_sequences;
                // Get a label mapping for this
                if (! isset($valuesLabels['None'])) {
                    $valuesLabels['None'] = 'None';
                }
            }
        }

        // convert counts to a list of count objects
        $l = [];
        foreach ($valuesCounts as $val => $count) {
            $o = new \stdClass();
            if (array_key_exists($val, $valuesLabels)) {
                $o->name = $valuesLabels[$val];
            } else {
                $o->name = $val;
            }
            $o->count = $count;
            $l[] = $o;
        }

        return $l;
    }

    public static function generateChartsData($sample_list, $field_list, $field_map, $count_field = 'ir_sequence_count')
    {
        // Create an empty array, and keep track of which chart we are doing
        $chartsData = [];

        for ($chartCount = 0; $chartCount < count($field_list); $chartCount++) {
            // Get the field and the label
            $stat_field = $field_list[$chartCount];
            if (array_key_exists($stat_field, $field_map)) {
                $label_field = $field_map[$stat_field];
            } else {
                $label_field = $stat_field;
            }
            // The tag for the chart from a UI perspective is chartN
            $chartTag = 'chart' . strval($chartCount + 1);
            $chartsData[$chartTag] = [];
            // Title is short, human readable title, all upper case
            $title = strtoupper(__('short.' . $label_field));
            $chartsData[$chartTag]['title'] = $title;
            // Get the data for this field.
            $chartsData[$chartTag]['data'] = Sample::generateChartData($sample_list, $stat_field, $label_field, $count_field);
            // Log::debug($chartTag . ' ' . $stat_field . ' ' . $title);
        }

        return $chartsData;
    }
}
