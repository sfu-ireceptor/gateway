<?php

namespace App;

use Facades\App\RestService;
use Illuminate\Support\Facades\Log;

class Sample
{
    public static function public_samples()
    {
        return CachedSample::cached();
    }

    public static function metadata()
    {
        return CachedSample::metadata();
    }

    public static function find_sample_id_list($filters, $username)
    {
        // get samples
        $sample_data = self::find($filters, $username);
        $sample_list = $sample_data['items'];

        // generate list of sample ids
        $sample_id_list = [];
        foreach ($sample_list as $sample) {
            $sample_id_list['ir_project_sample_id_list_' . $sample->real_rest_service_id][] = $sample->ir_project_sample_id;
        }

        return $sample_id_list;
    }

    public static function find($filters, $username)
    {
        // remove gateway-specific filters
        unset($filters['open_filter_panel_list']);

        // do requests
        $response_list = RestService::samples($filters, $username);

        // if error, update gateway query status
        $gw_query_log_id = request()->get('query_log_id');
        foreach ($response_list as $response) {
            if ($response['status'] == 'error') {
                QueryLog::set_gateway_query_status($gw_query_log_id, 'service_error', $response['error_message']);
            }
        }

        // tweak responses
        $sample_list_all = [];
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
            }

            $sample_list = $response['data'];
            // dd($sample_list);
            if( ! is_array($sample_list)) {
                $sample_list = [];
            }

            foreach ($sample_list as $sample) {
                // add rest service id/name
                $sample->rest_service_id = $rs->id;
                $sample->rest_service_name = $rs->display_name;

                // add study URL
                $sample = self::generate_study_urls($sample);

                $sample_field_list = FieldName::getSampleFields();
                foreach ($sample_field_list as $sample_field) {
                    if ($sample_field['ir_id'] != 'organism') { // TODO decide with Scott the proper format and update mapping
                        // Log::debug($sample_field);
                        if (isset($sample_field['ir_adc_api_response'])) {
                            $field_name = $sample_field['ir_id'];
                            $field_value = data_get($sample, $sample_field['ir_adc_api_response']);
                            // if(is_object($field_value)) {
                            //     dd($field_value);
                            // }
                            $sample->{$field_name} = $field_value;
                        }
                    }
                }
            }
            $sample_list = FieldName::convertObjectList($sample_list, 'ir_v2', 'ir_id');

            $sample_list_all = array_merge($sample_list_all, $sample_list);
        }

        // return the statistics about that list of samples
        return self::stats($sample_list_all);
    }

    public static function stats($sample_list)
    {
        // group samples by service
        $samples_by_rs = [];
        foreach ($sample_list as $sample) {
            $rs_id = $sample->rest_service_id;

            if (! isset($samples_by_rs[$rs_id])) {
                $samples_by_rs[$rs_id]['name'] = $sample->rest_service_name;
                $samples_by_rs[$rs_id]['sample_list'] = [];
            }

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

        foreach ($samples_by_rs as $t) {
            $rs_name = $t['name'];
            $sample_list = $t['sample_list'];

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

                if (! isset($sample->study_title)) {
                    $sample->study_title = '';
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
            $rs_data['rs_name'] = $rs_name;
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
                $filtered_repositories[] = $rs_data['rs_name'];
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
                $pmid = preg_replace('~\D~', '', $sample->pub_ids);
                $sample->study_url = 'https://www.ncbi.nlm.nih.gov/pubmed/' . $pmid;
            } elseif (is_url($sample->pub_ids)) {
                $sample->study_url = $sample->pub_ids;
            }
        }

        return $sample;
    }
}
