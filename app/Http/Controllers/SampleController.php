<?php

namespace App\Http\Controllers;

use App\Bookmark;
use App\FieldName;
use App\Query;
use App\QueryLog;
use App\RestService;
use App\RestServiceGroup;
use App\Sample;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SampleController extends Controller
{
    protected const DEFAULT_FIELDS = ['full_text_search', 'study_id', 'study_title', 'study_type_id', 'study_group_description', 'lab_name', 'subject_id', 'organism_id', 'sex', 'ethnicity', 'ir_subject_age_min', 'ir_subject_age_max', 'age_unit_id', 'disease_diagnosis_id', 'sample_id', 'pcr_target_locus', 'cell_subset_id', 'tissue_id', 'template_class', 'cell_phenotype', 'sequencing_platform'];
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

    public function postIndex(Request $request, $type = '')
    {
        $page_uri = 'samples';
        if ($type != '') {
            $page_uri = 'samples/' . $type;
        }

        $query_id = Query::saveParams($request->except(['_token']), $page_uri);

        return redirect($page_uri . '?query_id=' . $query_id)->withInput();
    }

    public function index(Request $request, $type = '')
    {
        $page_uri = 'samples';
        if ($type != '') {
            $page_uri = 'samples/' . $type;
        }

        $type_full = 'sequence';
        if ($type != '') {
            $type_full = $type;
        }

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

            return redirect($page_uri . '?query_id=' . $new_query_id);
        }

        // if there's a "page" parameter, generate new query_id and redirect to it
        if ($request->has('page')) {
            $filters = Query::getParams($request->input('query_id'));
            $filters['page'] = $request->input('page');
            $new_query_id = Query::saveParams($filters, 'samples');

            return redirect($page_uri . '?query_id=' . $new_query_id);
        }

        // if there's a "rest_service_name" parameter, generate new query_id and redirect to it
        if ($request->has('rest_service_name')) {
            $filters = Query::getParams($request->input('rest_service_name'));
            $filters['rest_service_name'] = $request->input('rest_service_name');
            $new_query_id = Query::saveParams($filters, 'samples');

            return redirect($page_uri . '?query_id=' . $new_query_id);
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

            return redirect($page_uri . '?query_id=' . $new_query_id);
        }

        // if no filters and there's cached data, immediately return cached data
        if (! $request->has('query_id')) {
            $cached_data = Cache::get('samples-no-filters-data');
            if ($cached_data != null) {
                return view('sample', $cached_data);
            }
        }

        /*************************************************
        * prepare form data */

        // get data
        $metadata = Sample::metadata($username);

        // study type ontology info
        $study_type_ontology_list = [];
        foreach ($metadata['study_type_id'] as $v) {
            $study_type_ontology_list[$v['id']] = $v['label'] . ' (' . $v['id'] . ')';
        }

        // gender
        $subject_gender_list = [];
        foreach ($metadata['sex'] as $v) {
            $subject_gender_list[$v] = $v;
        }

        // organism ontology info
        $subject_organism_ontology_list = [];
        foreach ($metadata['organism_id'] as $v) {
            $subject_organism_ontology_list[$v['id']] = $v['label'] . ' (' . $v['id'] . ')';
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
        $cell_type_ontology_list = [];
        foreach ($metadata['cell_subset_id'] as $v) {
            $cell_type_ontology_list[$v['id']] = $v['label'] . ' (' . $v['id'] . ')';
        }

        // tissue ontology info
        $sample_tissue_ontology_list = [];
        foreach ($metadata['tissue_id'] as $v) {
            $sample_tissue_ontology_list[$v['id']] = $v['label'] . ' (' . $v['id'] . ')';
        }

        // dna type
        $dna_type_list = [];
        foreach ($metadata['template_class'] as $v) {
            $dna_type_list[$v] = $v;
        }

        // age_unit ontology info
        $subject_age_unit_ontology_list = [];
        foreach ($metadata['age_unit_id'] as $v) {
            $subject_age_unit_ontology_list[$v['id']] = $v['label'] . ' (' . $v['id'] . ')';
        }

        // disease_diagnosis ontology info
        $subject_disease_diagnosis_ontology_list = [];
        foreach ($metadata['disease_diagnosis_id'] as $v) {
            $subject_disease_diagnosis_ontology_list[$v['id']] = $v['label'] . ' (' . $v['id'] . ')';
        }

        // data
        $data = [];

        $data['page_uri'] = $page_uri;

        $data['study_type_ontology_list'] = $study_type_ontology_list;
        $data['subject_gender_list'] = $subject_gender_list;
        $data['subject_ethnicity_list'] = $subject_ethnicity_list;
        $data['subject_organism_ontology_list'] = $subject_organism_ontology_list;
        $data['subject_age_unit_ontology_list'] = $subject_age_unit_ontology_list;
        $data['subject_disease_diagnosis_ontology_list'] = $subject_disease_diagnosis_ontology_list;
        $data['pcr_target_locus_list'] = $pcr_target_locus_list;
        $data['cell_type_ontology_list'] = $cell_type_ontology_list;
        $data['sample_tissue_ontology_list'] = $sample_tissue_ontology_list;
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

        $sample_data = Sample::find($params, $username, true, $type);

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
        // Add a repository URL field to the sample
        foreach ($sample_list as $key => $sample) {
            // NOTE: We use the real_rest_service_id, which is the repository service
            // id. rest_service_id is the repository group id - we want the real
            // repository.
            if (isset($sample->real_rest_service_id)) {
                $rs = RestService::find($sample->real_rest_service_id);
                $sample_list[$key]->rest_service_info_url = $rs->url . 'info';
                $sample_list[$key]->rest_service_base_url = $rs->baseURL();
            }
        }
        $samples_with_sequences = Sample::sort_sample_list($sample_list, $sort_column, $sort_order);

        // Fields we want to graph. The UI/blade expects six fields
        $charts_fields = ['study_type_id', 'organism', 'disease_diagnosis_id',
            'tissue_id', 'pcr_target_locus', 'template_class', 'cell_subset', ];
        // Mapping of fields to display as labels on the graph for those that need
        // mappings. These are usually required for ontology fields where we want
        // to aggregate on the ontology ID but display the ontology label.
        $field_map = ['study_type_id' => 'study_type',
            'disease_diagnosis_id' => 'disease_diagnosis',
            'tissue_id' => 'tissue', ];
        $data['charts_data'] = Sample::generateChartsData($sample_list, $charts_fields, $field_map);

        $data['sequence_charts_data'] = Sample::generateChartsData($samples_with_sequences, $charts_fields, $field_map, 'ir_' . $type_full . '_count');

        // keep only samples to display on the current page
        $samples_with_sequences = array_slice($samples_with_sequences, ($page - 1) * $max_per_page, $max_per_page);

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
        $data['nb_samples'] = $nb_samples;
        $data['nb_pages'] = $nb_pages;
        $data['page'] = $page;
        $data['page_first_element_index'] = ($page - 1) * $max_per_page + 1;
        $data['page_last_element_index'] = $data['page_first_element_index'] + count($samples_with_sequences) - 1;

        $tab = $type;
        if ($type == '') {
            $tab = 'sequence';
        }
        $data['tab'] = $tab;

        $data['sort_column'] = $sort_column;
        $data['sort_order'] = $sort_order;
        $data['sequences_query_id'] = $sequences_query_id;
        $data['rest_service_list'] = $sample_data['rs_list'];

        $data['total_filtered_repositories'] = $sample_data['total_filtered_repositories'];
        $data['total_filtered_labs'] = $sample_data['total_filtered_labs'];
        $data['total_filtered_studies'] = $sample_data['total_filtered_studies'];
        $data['total_filtered_samples'] = $sample_data['total_filtered_samples'];
        $data['total_filtered_objects'] = $sample_data['total_filtered_objects'];

        $data['nb_samples_with_sequences'] = $sample_data['nb_samples_with_sequences'];
        $data['nb_samples_with_clones'] = $sample_data['nb_samples_with_clones'];
        $data['nb_samples_with_cells'] = $sample_data['nb_samples_with_cells'];

        // List of repositories that didn't respond. Note we list repository group not
        // individual repositories here.
        $rs_list_no_response = $sample_data['rs_list_no_response'];
        $rs_list_no_response_names = [];
        foreach ($rs_list_no_response as $rs) {
            // If the service has a group, get the group name, otherwise use repository name.
            if ($rs->rest_service_group_code == null) {
                $name = $rs->name;
            } else {
                $name = RestServiceGroup::nameForCode($rs->rest_service_group_code);
            }
            $rs_list_no_response_names[] = $name;
        }
        if (! empty($rs_list_no_response_names)) {
            $data['rs_list_no_response_str'] = 'Incomplete response was received from <strong>' . implode(', ', $rs_list_no_response_names) . '</strong>.';
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
                    // If the field is an ontology field, we want the filter fields to
                    // have both label and ID.
                    if (in_array($k, FieldName::getOntologyFields())) {
                        // Get the base field (without the _id part). This is how the
                        // metadata is tagged.
                        $filter_info = '';
                        // For each element in the filter parameters... This is essentially
                        // the list of filters that are set.
                        foreach ($v as $element) {
                            // Get the cahced metadata for the field so we can build a label/id string
                            $field_metadata = $metadata[$k];
                            // Find the element in the metadata and build the filter label string.
                            foreach ($field_metadata as $field_info) {
                                if ($field_info['id'] == $element) {
                                    if ($filter_info != '') {
                                        $filter_info = $filter_info . ', ';
                                    }
                                    $filter_info = $filter_info . $field_info['label'] . ' (' . $field_info['id'] . ')';
                                }
                            }
                            $filter_fields[$k] = $filter_info;
                        }
                    } else {
                        // If it is a normal array filter, then combine the stings
                        $filter_fields[$k] = implode(', ', $v);
                    }
                } else {
                    // If it is a simple string filter, then use the value directly.
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

        if (! $request->has('query_id')) {
            Cache::put('samples-no-filters-data', $data);
        }

        // Return the data to the view.
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
        //$file_path = $t['public_path'];
        $file_path = $t['system_path'];

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
        $file_path = $t['system_path'];

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
