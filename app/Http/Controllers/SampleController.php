<?php

namespace App\Http\Controllers;

use App\Bookmark;
use App\FieldName;
use App\Query;
use App\QueryLog;
use App\Sample;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SampleController extends Controller
{
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
                // remove all filters but columns filters
                $new_filters = [];
                foreach ($filters as $name => $value) {
                    if ($name == 'cols') {
                        $new_filters[$name] = $value;
                    }
                }
            } else {
                // remove only that filter
                unset($filters[$filter_to_remove]);
                $new_filters = $filters;
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

        // if there's a "browse_sequences" parameter, redirect to appropriate sequences URL
        if ($request->has('browse_sequences')) {
            $samples_query_id = $request->input('query_id');
            $samples_filters = Query::getParams($samples_query_id);
            if(isset($samples_filters['page'])) {
                unset($samples_filters['page']);
            }
            $sample_data = Sample::find($samples_filters, $username);

            $sequence_filters = [];
            $sequence_filters['sample_query_id'] = $samples_query_id;
            foreach ($sample_data['items'] as $sample) {
                $rs_id = $sample->rest_service_id;
                $rs_param = 'ir_project_sample_id_list_' . $rs_id;
                if( ! isset($sequence_filters[$rs_param])) {
                    $sequence_filters[$rs_param] = [];
                }
                $sequence_filters[$rs_param][] = $sample->repertoire_id;
            }

            $sequences_query_id = Query::saveParams($sequence_filters, 'sequences');

            return redirect('sequences?query_id=' . $sequences_query_id);
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
        $subject_organism_list[''] = 'Any';
        foreach ($metadata['organism'] as $v) {
            $subject_organism_list[$v] = $v;
        }

        // ethnicity
        $subject_ethnicity_list = [];
        foreach ($metadata['ethnicity'] as $v) {
            $subject_ethnicity_list[$v] = $v;
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

        // data
        $data = [];
        $data['study_type_list'] = $study_type_list;
        $data['subject_gender_list'] = $subject_gender_list;
        $data['subject_ethnicity_list'] = $subject_ethnicity_list;
        $data['subject_organism_list'] = $subject_organism_list;
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

        // TODO put in config
        $max_per_page = 10;

        $nb_samples = count($sample_data['items']);

        // nb of pages
        $nb_pages = (int) ceil($nb_samples / $max_per_page);
        // dd($nb_pages);

        // adjust current page number if necessary
        if ($page < 1) {
            $page = 1;
        }
        if ($page > $nb_pages) {
            $page = $nb_pages;
        }

        // TODO check page is valid

        $sample_list = $sample_data['items'];
        // dd(count($sample_data['items']));

        // TODO sort sample list

        $sample_list = array_slice($sample_list, ($page - 1) * $max_per_page, $max_per_page);
        // dd(count($sample_list));

        $data['sample_list'] = $sample_list;
        $data['nb_samples'] = $nb_samples;
        $data['nb_pages'] = $nb_pages;
        $data['page'] = $page;
        $data['rest_service_list'] = $sample_data['rs_list'];
        $data['sample_list_json'] = json_encode($sample_data['items']);

        $data['total_filtered_repositories'] = $sample_data['total_filtered_repositories'];
        $data['total_filtered_labs'] = $sample_data['total_filtered_labs'];
        $data['total_filtered_studies'] = $sample_data['total_filtered_studies'];
        $data['total_filtered_samples'] = $sample_data['total_filtered_samples'];
        $data['total_filtered_sequences'] = $sample_data['total_filtered_sequences'];

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

    public function stats(Request $request)
    {
        // get sample list
        $params = $request->all();
        $params['ajax'] = true;

        $sample_data = Sample::find($params);
        $sample_list = $sample_data['items'];

        return json_encode($sample_list);
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

        return redirect($file_path);
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

        return redirect($file_path);
    }
}
