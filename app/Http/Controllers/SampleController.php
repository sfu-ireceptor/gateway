<?php

namespace App\Http\Controllers;

use App\Query;
use App\Sample;
use App\Bookmark;
use App\QueryLog;
use App\FieldName;
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
        // if "remove one filter" request, generate new query_id and redirect to it
        if ($request->has('remove_filter')) {
            $filters = Query::getParams($request->input('query_id'));
            $filter_to_remove = $request->input('remove_filter');

            unset($filters[$filter_to_remove]);
            $new_query_id = Query::saveParams($filters, 'samples');

            return redirect('samples?query_id=' . $new_query_id);
        }

        $username = auth()->user()->username;

        /*************************************************
        * prepare form data */

        // get data
        $metadata = Sample::metadata($username);

        // gender
        $subject_gender_list = [];
        $subject_gender_list[''] = 'Any';
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
        }

        // fill form fields accordingly
        $request->session()->forget('_old_input');
        $request->session()->put('_old_input', $params);

        $data['sample_query_id'] = $query_id;

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

        $data['sample_list'] = $sample_data['items'];
        $data['rest_service_list'] = $sample_data['rs_list'];
        $data['sample_list_json'] = json_encode($sample_data['items']);

        $data['total_filtered_repositories'] = $sample_data['total_filtered_repositories'];
        $data['total_filtered_labs'] = $sample_data['total_filtered_labs'];
        $data['total_filtered_studies'] = $sample_data['total_filtered_studies'];
        $data['total_filtered_samples'] = $sample_data['total_filtered_samples'];
        $data['total_filtered_sequences'] = $sample_data['total_filtered_sequences'];

        $data['filtered_repositories_names'] = implode(', ', $sample_data['filtered_repositories']);

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
}
