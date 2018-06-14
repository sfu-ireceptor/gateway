<?php

namespace App;

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
    	return RestService::sequencesTSV($filters, $username, $gw_query_log_id, $url, $sample_filters);
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
