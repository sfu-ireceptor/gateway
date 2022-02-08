<?php

namespace App\Http\Controllers;

use App\Bookmark;
use App\FieldName;
use App\Query;
use App\QueryLog;
use App\RestService;
use App\Sample;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SampleController extends Controller
{
    protected const DEFAULT_FIELDS = ['full_text_search', 'study_id', 'study_title', 'study_type', 'study_group_description', 'lab_name', 'subject_id', 'organism', 'sex', 'ethnicity', 'ir_subject_age_min', 'ir_subject_age_max', 'disease_diagnosis', 'sample_id', 'pcr_target_locus', 'cell_subset', 'tissue', 'template_class', 'cell_phenotype', 'sequencing_platform'];
    protected $extra_fields = [];

    public function __construct()
    {
        // init $extra_fields
        $all_fields = FieldName::getSampleFields();
        foreach ($all_fields as $field) {
            $field_id = $field['ir_id'];
            if (! in_array($field_id, self::DEFAULT_FIELDS)) {
                $this->extra_fields[] = $field_id;
            }
        }
    }

    public function is_extra_field($field_id)
    {
        return in_array($field_id, $this->extra_fields);
    }

    public function postIndex(Request $request)
    {
        $query_id = Query::saveParams($request->except(['_token']), 'samples');

        return redirect('samples?query_id=' . $query_id)->withInput();
    }

    public function index(Request $request)
    {
        $username = auth()->user()->username;

        // if "remove one filter" request, generate new query_id and redirect to it
        if ($request->has('remove_filter')) {
            $filters = Query::getParams($request->input('query_id'));
            $filter_to_remove = $request->input('remove_filter');

            if ($filter_to_remove == 'all') {
                // remove all filters but columns filters and extra filters values
                $new_filters = [];
                foreach ($filters as $name => $value) {
                    if ($name == 'cols') {
                        $new_filters[$name] = $value;
                    } elseif ($this->is_extra_field($name)) {
                        $new_filters[$name] = null;
                    }
                }
            } else {
                // remove only that filter
                if ($this->is_extra_field($filter_to_remove)) {
                    $filters[$filter_to_remove] = null;
                } else {
                    unset($filters[$filter_to_remove]);
                }
                $new_filters = $filters;
            }

            // remove page filter
            if (isset($new_filters['page'])) {
                unset($new_filters['page']);
            }

            $new_query_id = Query::saveParams($new_filters, 'samples');

            return redirect('samples?query_id=' . $new_query_id);
        }

        // if there's a "page" parameter, generate new query_id and redirect to it
        if ($request->has('page')) {
            $filters = Query::getParams($request->input('query_id'));
            $filters['page'] = $request->input('page');
            $new_query_id = Query::saveParams($filters, 'samples');

            return redirect('samples?query_id=' . $new_query_id);
        }

        // if there's a "sort_column" parameter, generate new query_id and redirect to it
        if ($request->has('sort_column')) {
            $filters = Query::getParams($request->input('query_id'));
            $filters['sort_column'] = $request->input('sort_column');
            $filters['sort_order'] = $request->input('sort_order', 'asc');

            // keep current columns
            if ($request->has('cols')) {
                $filters['cols'] = $request->input('cols');
            }

            $new_query_id = Query::saveParams($filters, 'samples');

            return redirect('samples?query_id=' . $new_query_id);
        }

        /*************************************************
        * prepare form data */

        // get data
        $metadata = Sample::metadata($username);

        // study type
        $study_type_list = [];
        foreach ($metadata['study_type'] as $v) {
            $study_type_list[$v] = $v;
        }

        // gender
        $subject_gender_list = [];
        foreach ($metadata['sex'] as $v) {
            $subject_gender_list[$v] = $v;
        }

        // organism
        $subject_organism_list = [];
        foreach ($metadata['organism'] as $v) {
            $subject_organism_list[$v] = $v;
        }

        // ethnicity
        $subject_ethnicity_list = [];
        foreach ($metadata['ethnicity'] as $v) {
            $subject_ethnicity_list[$v] = $v;
        }

        // target locus for PCR
        $pcr_target_locus_list = [];
        foreach ($metadata['pcr_target_locus'] as $v) {
            $pcr_target_locus_list[$v] = $v;
        }

        // cell type
        $cell_type_list = [];
        foreach ($metadata['cell_subset'] as $v) {
            $cell_type_list[$v] = $v;
        }

        // sample source
        $sample_source_list = [];
        foreach ($metadata['tissue'] as $v) {
            $sample_source_list[$v] = $v;
        }

        // dna type
        $dna_type_list = [];
        foreach ($metadata['template_class'] as $v) {
            $dna_type_list[$v] = $v;
        }

        // disease_diagnosis
        $subject_disease_diagnosis_list = [];
        foreach ($metadata['disease_diagnosis'] as $v) {
            $subject_disease_diagnosis_list[$v] = $v;
        }

        // data
        $data = [];
        $data['study_type_list'] = $study_type_list;
        $data['subject_gender_list'] = $subject_gender_list;
        $data['subject_ethnicity_list'] = $subject_ethnicity_list;
        $data['subject_organism_list'] = $subject_organism_list;
        $data['subject_disease_diagnosis_list'] = $subject_disease_diagnosis_list;
        $data['pcr_target_locus_list'] = $pcr_target_locus_list;
        $data['cell_type_list'] = $cell_type_list;
        $data['sample_source_list'] = $sample_source_list;
        $data['dna_type_list'] = $dna_type_list;

        /******************************************************
        * get repository global statistics (unfiltered data) */

        $data['total_repositories'] = $metadata['total_repositories'];
        $data['total_labs'] = $metadata['total_labs'];
        $data['total_studies'] = $metadata['total_projects'];
        $data['total_samples'] = $metadata['total_samples'];
        $data['total_sequences'] = $metadata['total_sequences'];

        /*************************************************
        * retrieve filters */

        $query_id = '';
        $params = [];

        if ($request->has('query_id')) {
            $query_id = $request->input('query_id');
            $params = Query::getParams($query_id);
            $data['query_id'] = $query_id;
        }

        // fill form fields accordingly
        $request->session()->forget('_old_input');
        $request->session()->put('_old_input', $params);

        $data['sample_query_id'] = $query_id;

        // get page parameter
        $page = 1;
        if (isset($params['page'])) {
            $page = (int) $params['page'];
            unset($params['page']);
        }

        // get sorting parameters
        $default_sort_column = 'ir_sequence_count';
        $default_sort_order = 'desc';

        $sort_column = $default_sort_column;
        $sort_order = $default_sort_order;

        if (isset($params['sort_column'])) {
            $sort_column = $params['sort_column'];
            $sort_order = $params['sort_order'];

            unset($params['sort_column']);
            unset($params['sort_order']);
        }

        // remove value from dropdown to pick an extra field
        if (isset($params['extra_field'])) {
            unset($params['extra_field']);
        }

        /*************************************************
        * get filtered sample list and related statistics */

        $sample_data = Sample::find($params, $username);

        // log result
        $query_log_id = $request->get('query_log_id');
        if ($query_log_id != null) {
            $query_log = QueryLog::find($query_log_id);
            $query_log->result_size = $sample_data['total_filtered_samples'];
            $query_log->save();
        }

        $max_per_page = config('ireceptor.nb_samples_per_page');
        $nb_samples = count($sample_data['items']);
        $nb_pages = (int) ceil($nb_samples / $max_per_page);

        // adjust current page number if necessary
        if ($page < 1) {
            $page = 1;
        }

        if ($page > $nb_pages) {
            $page = $nb_pages;
        }

        $sample_list = $sample_data['items'];

        // $sample_list2 = [];
        // foreach ($sample_list as $sample) {
        //     // PRJNA330606-268
        //     if($sample->repertoire_id == '17' || $sample->repertoire_id == 'PRJNA330606-268' || $sample->repertoire_id == '5890931441127648790-242ac113-0001-012') {
        //         $sample_list2[] = $sample;
        //     }
        // }

        // $sample_list = $sample_list2;

        $samples_with_sequences = [];
        $samples_with_clones = [];
        $samples_with_cells = [];

        foreach ($sample_list as $sample) {
            if (isset($sample->ir_cell_count) && $sample->ir_cell_count > 0) {
                $samples_with_cells[] = $sample;
            }
            elseif (isset($sample->ir_clone_count) && $sample->ir_clone_count > 0) {
                $samples_with_clones[] = $sample;
            } else {
                $samples_with_sequences[] = $sample;
            }
        }

        // sort sample lists
        $sort_column_sequences = $sort_column;
        $sort_column_clones = $sort_column;
        if ($sort_column == 'ir_sequence_count' || $sort_column == 'ir_clone_count' || $sort_column == 'ir_cell_count') {
            $sort_column_sequences = 'ir_sequence_count';
            $sort_column_clones = 'ir_clone_count';
            $sort_column_cells = 'ir_cell_count';
        }

        $samples_with_sequences = Sample::sort_sample_list($samples_with_sequences, $sort_column_sequences, $sort_order);
        $samples_with_clones = Sample::sort_sample_list($samples_with_clones, $sort_column_clones, $sort_order);
        $samples_with_cells = Sample::sort_sample_list($samples_with_cells, $sort_column_cells, $sort_order);

        $sequence_charts_fields = ['study_type', 'organism', 'disease_diagnosis', 'tissue', 'pcr_target_locus', 'template_class'];
        $data['sequence_charts_data'] = Sample::generateChartsData($samples_with_sequences, $sequence_charts_fields);

        $clone_charts_fields = ['study_type', 'organism', 'disease_diagnosis', 'tissue', 'pcr_target_locus', 'template_class'];
        $data['clone_charts_data'] = Sample::generateChartsData($samples_with_clones, $clone_charts_fields, 'ir_clone_count');

        $cell_charts_fields = ['study_type', 'organism', 'disease_diagnosis', 'tissue', 'pcr_target_locus', 'template_class'];
        $data['cell_charts_data'] = Sample::generateChartsData($samples_with_cells, $cell_charts_fields, 'ir_cell_count');

        // keep only samples to display on the current page
        $samples_with_sequences = array_slice($samples_with_sequences, ($page - 1) * $max_per_page, $max_per_page);
        $samples_with_clones = array_slice($samples_with_clones, ($page - 1) * $max_per_page, $max_per_page);
        $samples_with_cells = array_slice($samples_with_cells, ($page - 1) * $max_per_page, $max_per_page);

        // add flag to first sample with stats for stats info popup
        if (auth()->user()->stats_popup_count <= 0) {
            Log::debug('stat popup notification will show for ' . auth()->user()->username);
            foreach ($samples_with_sequences as $sample) {
                if (isset($sample->stats) && $sample->stats) {
                    $sample->show_stats_notification = true;
                    break;
                }
            }
        }

        // generate query id for sequences page
        $sequence_filters = [];
        $sequence_filters['sample_query_id'] = $query_id;
        foreach ($sample_data['items'] as $sample) {
            $rs_id = $sample->real_rest_service_id;
            $rs_param = 'ir_project_sample_id_list_' . $rs_id;
            if (! isset($sequence_filters[$rs_param])) {
                $sequence_filters[$rs_param] = [];
            }
            $sequence_filters[$rs_param][] = $sample->repertoire_id;
        }
        $sequences_query_id = Query::saveParams($sequence_filters, 'sequences');

        // prepare view data
        $data['samples_with_sequences'] = $samples_with_sequences;
        $data['samples_with_clones'] = $samples_with_clones;
        $data['samples_with_cells'] = $samples_with_cells;
        $data['nb_samples'] = $nb_samples;
        $data['nb_pages'] = $nb_pages;
        $data['page'] = $page;
        $data['page_first_element_index'] = ($page - 1) * $max_per_page + 1;
        $data['page_last_element_index'] = $data['page_first_element_index'] + count($samples_with_sequences) - 1;

        $tab = 'sequences';
        if (isset($params['tab'])) {
            $tab = $params['tab'];
        }
        $data['tab'] = $tab;

        $data['sort_column'] = $sort_column;
        $data['sort_column_sequences'] = $sort_column_sequences;
        $data['sort_column_clones'] = $sort_column_clones;
        $data['sort_column_cells'] = $sort_column_cells;
        $data['sort_order'] = $sort_order;
        $data['sequences_query_id'] = $sequences_query_id;
        $data['rest_service_list'] = $sample_data['rs_list'];

        $data['total_filtered_repositories'] = $sample_data['total_filtered_repositories'];
        $data['total_filtered_labs'] = $sample_data['total_filtered_labs'];
        $data['total_filtered_studies'] = $sample_data['total_filtered_studies'];
        $data['total_filtered_samples'] = $sample_data['total_filtered_samples'];
        $data['total_filtered_sequences'] = $sample_data['total_filtered_sequences'];
        $data['total_filtered_clones'] = $sample_data['total_filtered_clones'];
        $data['total_filtered_cells'] = $sample_data['total_filtered_cells'];

        $data['filtered_repositories_names'] = implode(', ', $sample_data['filtered_repositories']);

        // list of repositories that didn't respond
        $rs_list_no_response = $sample_data['rs_list_no_response'];
        $rs_list_no_response_names = [];
        foreach ($rs_list_no_response as $rs) {
            $rs_list_no_response_names[] = $rs->name;
        }
        if (! empty($rs_list_no_response_names)) {
            $data['rs_list_no_response_str'] = 'No response was received from <strong>' . implode(', ', $rs_list_no_response_names) . '</strong>.';
        } else {
            $data['rs_list_no_response_str'] = '';
        }

        // list of repositories that didn't return the sequence counts
        $rs_list_sequence_count_error = $sample_data['rs_list_sequence_count_error'];
        $rs_list_sequence_count_error_names = [];
        foreach ($rs_list_sequence_count_error as $rs) {
            $rs_list_sequence_count_error_names[] = $rs->name;
        }
        if (! empty($rs_list_sequence_count_error_names)) {
            $data['rs_list_sequence_count_error_str'] = 'The number of sequences (on the left) and the charts (below) don\'t include <strong>' . implode(', ', $rs_list_sequence_count_error_names) . '</strong> because the number of sequences couldn\'t be retrieved.';
        } else {
            $data['rs_list_sequence_count_error_str'] = '';
        }

        // create copy of filters for display
        $filter_fields = [];
        foreach ($params as $k => $v) {
            if ($v) {
                if (is_array($v)) {
                    $filter_fields[$k] = implode(', ', $v);
                } else {
                    $filter_fields[$k] = $v;
                }
            }
        }

        // remove gateway-specific params
        unset($filter_fields['cols']);
        unset($filter_fields['tab']);
        unset($filter_fields['open_filter_panel_list']);
        $data['filter_fields'] = $filter_fields;

        // for bookmarking
        $current_url = $request->fullUrl();
        $data['url'] = $current_url;
        $data['bookmark_id'] = Bookmark::getIdFromURl($current_url, auth()->user()->id);

        // keep filters panels open
        $open_filter_panel_list = [];
        if (isset($params['open_filter_panel_list'])) {
            $open_filter_panel_list = $params['open_filter_panel_list'];
        }
        $data['open_filter_panel_list'] = $open_filter_panel_list;

        // get sample fields
        $field_list = FieldName::getSampleFields();
        $data['field_list'] = $field_list;

        // get sample fields grouped
        $field_list_grouped = FieldName::getSampleFieldsGrouped();
        $data['field_list_grouped'] = $field_list_grouped;

        // retrieve all fields
        $all_fieds = [];
        foreach ($field_list_grouped as $group) {
            $group_name = $group['name'];
            foreach ($group['fields'] as $field) {
                $all_fieds[$field['ir_id']] = $group_name . ' | ' . $field['ir_short'];
            }
        }

        // build list of extra fields: remvove fields already hard coded in the view
        $extra_fields = [];
        foreach ($all_fieds as $k => $v) {
            if (! in_array($k, self::DEFAULT_FIELDS)) {
                $extra_fields[$k] = $v;
            }
        }
        $data['extra_fields'] = $extra_fields;

        // build list of extra parameters (list extra fields actually used)
        $extra_params = [];
        foreach ($extra_fields as $k => $v) {
            if (array_key_exists($k, $params)) {
                $extra_params[] = $k;
            }
        }
        $data['extra_params'] = $extra_params;

        // build list of disabled fields for extra fields dropdown
        $extra_fields_options_attributes = [];
        foreach ($extra_params as $k => $v) {
            $extra_fields_options_attributes[$v] = ['disabled' => 'disabled'];
        }
        $data['extra_fields_options_attributes'] = $extra_fields_options_attributes;

        // table columns to display
        if (isset($params['cols'])) {
            $current_columns = explode(',', $params['cols']);
        } else {
            $current_columns = [];
            foreach ($field_list as $field) {
                if ($field['default_visible']) {
                    $current_columns[] = $field['ir_id'];
                }
            }
        }
        $data['current_columns'] = $current_columns;

        // string value for hidden field
        $current_columns_str = implode(',', $current_columns);
        $data['current_columns_str'] = $current_columns_str;

        return view('sample', $data);
    }

    public function stats($rest_service_id, $repertoire_id, $stat, Request $request)
    {
        $stats_str = RestService::stats($rest_service_id, $repertoire_id, $stat);
        $stats = json_decode($stats_str);

        $t = [];
        $t['stats'] = $stats;

        return $t;
    }

    public function stats_sample_info($rest_service_id, $repertoire_id, Request $request)
    {
        $rs = RestService::find($rest_service_id);

        $response = RestService::samples(['repertoire_id' => $repertoire_id], '', true, [$rest_service_id], false);
        $sample_list = Sample::convert_sample_list($response[0]['data'], $rs);
        $sample = $sample_list[0];

        $data = [];
        $data['sample'] = $sample;

        return view('stats_sample_info', $data);
    }

    public function json(Request $request)
    {
        $username = auth()->user()->username;

        $query_id = '';
        $params = [];

        if ($request->has('query_id')) {
            $query_id = $request->input('query_id');
            $params = Query::getParams($query_id);
            $data['query_id'] = $query_id;
        }

        $t = Sample::samplesJSON($params, $username);
        $file_path = $t['public_path'];

        // log result
        $query_log_id = $request->get('query_log_id');
        if ($query_log_id != null) {
            $query_log = QueryLog::find($query_log_id);
            $query_log->result_size = $t['size'];
            $query_log->save();
        }

        return response()->download($file_path);
    }

    public function tsv(Request $request)
    {
        $username = auth()->user()->username;

        $query_id = '';
        $params = [];

        if ($request->has('query_id')) {
            $query_id = $request->input('query_id');
            $params = Query::getParams($query_id);
            $data['query_id'] = $query_id;
        }

        $t = Sample::samplesTSV($params, $username);
        $file_path = $t['public_path'];

        // log result
        $query_log_id = $request->get('query_log_id');
        if ($query_log_id != null) {
            $query_log = QueryLog::find($query_log_id);
            $query_log->result_size = $t['size'];
            $query_log->save();
        }

        return response()->download($file_path);
    }

    public function field($id)
    {
        $field = FieldName::getField($id);

        $data = [];
        $data['field'] = $field;

        return view('field', $data);
    }

    public function field_data($id)
    {
        $field = FieldName::getField($id);

        $data = [];
        $data['field'] = $field;

        return $data;
    }

    public function countStatsPopupOpen(Request $request)
    {
        $user = auth()->user();
        $user->stats_popup_count += 1;
        $user->save();
    }
}
