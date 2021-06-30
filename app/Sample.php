<?php

namespace App;

use Carbon\Carbon;
use Facades\App\FieldName;
use Facades\App\RestService;
use Illuminate\Support\Facades\File;
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
                $sequence_count_array = RestService::sequence_count([$rest_service_id =>[$sample_id]], [], false);
                $sequence_count = $sequence_count_array[$rest_service_id]['samples'][$sample_id];
                $t['sequence_counts'][$sample_id] = $sequence_count;
                $total_sequence_count += $sequence_count;

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
                $clone_count_array = RestService::clone_count([$rest_service_id =>[$sample_id]], [], false);
                $clone_count = $clone_count_array[$rest_service_id]['samples'][$sample_id];
                $t['clone_counts'][$sample_id] = $clone_count;
                $total_clone_count += $clone_count;

                // HACK: to avoid hitting throttling limits
                sleep(1);
            }

            cloneCount::create($t);

            // cache total counts
            $rs = RestService::find($rest_service_id);
            $rs->nb_samples = count($sample_list);
            $rs->nb_clones = $total_clone_count;
            $rs->last_cached = new Carbon('now');
            $rs->save();
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

    public static function find($filters, $username, $count_sequences = true)
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

            $sample_list = $response_list[$i]['data'];
            $sample_list = self::convert_sample_list($sample_list, $rs);

            $sample_list_all = array_merge($sample_list_all, $sample_list);
        }

        // build list of services which didn't respond
        $rs_list_no_response = [];
        foreach ($response_list as $i => $response) {
            $rs = $response['rs'];

            if ($response['status'] == 'error') {
                if (isset($response['error_type'])) {
                    $rs->error_type = $response['error_type'];
                } else {
                    $rs->error_type = 'error';
                }
                $rs->error_type = $response['error_type'];
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
        $data = self::stats($sample_list_all);
        $data['rs_list_no_response'] = $rs_list_no_response;
        $data['rs_list_sequence_count_error'] = $rs_list_sequence_count_error;

        return $data;
    }

    // convert/complete sample list
    public static function convert_sample_list($sample_list, $rs)
    {
        $sample_field_list = FieldName::getSampleFields();

        $new_sample_list = [];
        foreach ($sample_list as $sample) {
            $new_sample = new \stdClass();

            foreach ($sample_field_list as $sample_field) {
                // Log::debug($sample_field);
                if (isset($sample_field['ir_adc_api_response'])) {
                    $field_name = $sample_field['ir_id'];
                    $field_value = data_get($sample, $sample_field['ir_adc_api_response']);
                    // if(is_object($field_value)) {
                    //     dd($field_value);
                    // }
                    $new_sample->{$field_name} = $field_value;
                }
            }

            // add extra fields (not defined in mapping file)
            $fields = ['repertoire_id', 'real_rest_service_id', 'ir_sequence_count', 'ir_clone_count', 'ir_filtered_sequence_count', 'stats'];
            foreach ($fields as $field_name) {
                if (isset($sample->{$field_name})) {
                    $new_sample->{$field_name} = $sample->{$field_name};
                }
            }

            // add rest service id/name
            $new_sample->rest_service_id = $rs->id;
            $new_sample->rest_service_name = $rs->display_name;

            // add study URL
            $new_sample = self::generate_study_urls($new_sample);

            $new_sample_list[] = $new_sample;
        }

        return $new_sample_list;
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
                if (isset($sample->ir_sequence_count) && is_numeric($sample->ir_sequence_count)) {
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
            $rs_data['total_filtered_sequences'] = $total_sequences;
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

        // sort alphabetically repositories/labs/studies
        $data['rs_list'] = self::sort_rest_service_list($data['rs_list']);

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
        $sample_data = self::find($filters, $username);
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
            $airr_sample = new \stdClass();
            foreach ($sample as $field_name => $field_value) {
                $is_airr_field = false;
                foreach ($sample_field_list as $sample_field) {
                    if ($sample_field['ir_id'] == $field_name && isset($sample_field['ir_adc_api_response'])) {
                        $airr_name = $sample_field['ir_adc_api_response'];
                        data_set_object($airr_sample, $airr_name, $field_value);
                        $is_airr_field = true;
                        break;
                    }
                }
                if (! $is_airr_field) {
                    $airr_sample->{'ir_' . $field_name} = $field_value;
                }
            }

            $obj->Repertoire[] = $airr_sample;
        }

        // generate JSON string from JSON structure
        $json = json_encode($obj, JSON_PRETTY_PRINT);

        // generate file name
        $storage_folder = storage_path() . '/app/public/';
        $now = time();
        $time_str = date('Y-m-d_Hi', $now);
        $file_name = 'ir_' . $time_str . '_' . uniqid() . '.json';
        $file_path = $storage_folder . $file_name;

        // write JSON string to file
        file_put_contents($file_path, $json);

        $public_path = 'storage' . str_after($file_path, storage_path('app/public'));

        $t = [];
        $t['size'] = filesize($file_path);
        $t['system_path'] = $file_path;
        $t['public_path'] = $public_path;

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

        // generate file name
        $storage_folder = storage_path() . '/app/public/';
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

        $public_path = 'storage' . str_after($file_path, storage_path('app/public'));

        $t = [];
        $t['size'] = filesize($file_path);
        $t['system_path'] = $file_path;
        $t['public_path'] = $public_path;

        return $t;
    }

    public static function sort_sample_list($sample_list, $sort_column, $sort_order)
    {
        $field_type = FieldName::getFieldType($sort_column);
        if ($sort_column == 'ir_sequence_count' || $sort_column == 'ir_clone_count') {
            $field_type = 'integer';
        }

        usort($sample_list, function ($a, $b) use ($sort_column, $sort_order, $field_type) {
            $comparison_result = 0;

            if ($sort_column == 'ir_sequence_count' || $sort_column == 'ir_clone_count') {
                if (! isset($a->{$sort_column})) {
                    $a->{$sort_column} = 0;
                }
                if (! isset($b->{$sort_column})) {
                    $b->{$sort_column} = 0;
                }
            }

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

            if ($sort_order == 'desc') {
                $comparison_result = -1 * $comparison_result;
            }

            return $comparison_result;
        });

        return $sample_list;
    }

    public static function generateChartData($sample_list, $field, $count_field = 'ir_sequence_count')
    {
        $valuesCounts = [];

        foreach ($sample_list as $sample) {
            $sample = json_decode(json_encode($sample), true);

            // nb of sequences for that sample
            $nb_sequences = 0;
            if (isset($sample[$count_field])) {
                $nb_sequences = $sample[$count_field];
            }

            // if the field has a non-null value, increase that value with the nb of sequences
            if (isset($sample[$field]) && $sample[$field] != null) {
                $value = $sample[$field];
                if (! isset($valuesCounts[$value])) {
                    $valuesCounts[$value] = 0;
                }
                $valuesCounts[$value] += $nb_sequences;
            }
            // else add the sequence count to the "None" value
            else {
                if (! isset($valuesCounts['None'])) {
                    $valuesCounts['None'] = 0;
                }

                $valuesCounts['None'] += $nb_sequences;
            }
        }

        // convert counts to a list of count objects
        $l = [];
        foreach ($valuesCounts as $val => $count) {
            $o = new \stdClass();
            $o->name = $val;
            $o->count = $count;
            $l[] = $o;
        }

        return $l;
    }

    public static function generateChartsData($sample_list, $field_list, $count_field = 'ir_sequence_count')
    {
        $chartsData = [];

        foreach ($field_list as $field) {
            $chartsData[$field] = [];
            $title = __('short.' . $field);
            if (! ctype_upper($title[1])) {
                // make lower case except for special cases like PCR target
                $title = strtolower($title);
            }

            $chartsData[$field]['title'] = $title;
            $chartsData[$field]['data'] = Sample::generateChartData($sample_list, $field, $count_field);
        }

        return $chartsData;
    }
}
