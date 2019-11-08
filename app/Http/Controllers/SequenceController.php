<?php

namespace App\Http\Controllers;

use App\Bookmark;
use App\FieldName;
use App\QueryLog;
use App\Sample;
use App\Sequence;
use App\System;
use Facades\App\Query;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class SequenceController extends Controller
{
    public function __construct()
    {
        $timeout = config('ireceptor.gateway_request_timeout');
        set_time_limit($timeout);
    }

    public function postIndex(Request $request)
    {
        $query_id = Query::saveParams($request->except(['_token']), 'sequences');

        return redirect('sequences?query_id=' . $query_id)->withInput();
    }

    public function index(Request $request)
    {
        // if no query id, generate one and redirect to it
        if (! $request->has('query_id')) {
            $query_id = Query::saveParams($request->except(['_token']), 'sequences');

            return redirect('sequences?query_id=' . $query_id)->withInput();
        }

        // if "remove one filter" request, generate new query_id and redirect to it
        if ($request->has('remove_filter')) {
            $filters = Query::getParams($request->input('query_id'));
            $filter_to_remove = $request->input('remove_filter');

            if ($filter_to_remove == 'all') {
                // remove all filters but sample filters and columns filters
                $new_filters = [];
                foreach ($filters as $name => $value) {
                    if (starts_with($name, 'ir_project_sample_id_list_') || $name == 'sample_query_id' || $name == 'cols') {
                        $new_filters[$name] = $value;
                    }
                }
            } else {
                // remove only that filter
                unset($filters[$filter_to_remove]);
                $new_filters = $filters;
            }

            $new_query_id = Query::saveParams($new_filters, 'sequences');

            return redirect('sequences?query_id=' . $new_query_id);
        }

        $username = auth()->user()->username;

        /*************************************************
        * prepare form data */

        // functional
        $functional_list = [];
        $functional_list[''] = 'Any';
        $functional_list['true'] = 'Yes';
        $functional_list['false'] = 'No';

        // annotation_tool
        $annotation_tool_list = [];
        $annotation_tool_list[''] = 'Any';
        $annotation_tool_list['MiXCR'] = 'MiXCR';
        $annotation_tool_list['V-Quest'] = 'V-Quest';
        $annotation_tool_list['IgBLAST'] = 'IgBLAST';

        // view data
        $data = [];
        $data['functional_list'] = $functional_list;
        $data['ir_annotation_tool_list'] = $annotation_tool_list;

        /*************************************************
        * retrieve filters */

        $query_id = $request->input('query_id');
        $filters = Query::getParams($query_id);

        // fill form fields accordingly
        $request->session()->forget('_old_input');
        $request->session()->put('_old_input', $filters);

        $data['query_id'] = $query_id;
        $data['sample_query_id'] = '';
        $sample_filter_fields = [];
        if (isset($filters['sample_query_id'])) {
            $sample_query_id = $filters['sample_query_id'];
            $data['sample_query_id'] = $sample_query_id;

            // sample filters for display
            $sample_filters = Query::getParams($sample_query_id);

            $sample_filter_fields = [];
            foreach ($sample_filters as $k => $v) {
                if ($v) {
                    if (is_array($v)) {
                        $sample_filter_fields[$k] = implode(', ', $v);
                    } else {
                        $sample_filter_fields[$k] = $v;
                    }
                }
            }
            // remove gateway-specific params
            unset($sample_filter_fields['open_filter_panel_list']);
            unset($sample_filter_fields['cols']);
        }
        $data['sample_filter_fields'] = $sample_filter_fields;

        // sequence list
        $sequence_data = Sequence::summary($filters, $username);

        // log result
        $query_log_id = $request->get('query_log_id');
        if ($query_log_id != null) {
            $query_log = QueryLog::find($query_log_id);
            $query_log->result_size = $sequence_data['total_filtered_sequences'];
            $query_log->save();
        }

        // summary for each REST service
        $rest_service_list = $sequence_data['rs_list'];
        foreach ($rest_service_list as $rs) {
            $summary = $rs['summary'];
        }

        $data['sequence_list'] = $sequence_data['items'];
        $data['sample_list_json'] = json_encode($sequence_data['summary']);
        $data['rest_service_list'] = $rest_service_list;
        $data['rest_service_list_no_response'] = $sequence_data['rs_list_no_response'];

        // Pass on the summary data from the sequence_data returned.
        $data['total_filtered_samples'] = $sequence_data['total_filtered_samples'];
        $data['total_filtered_repositories'] = $sequence_data['total_filtered_repositories'];
        $data['total_filtered_labs'] = $sequence_data['total_filtered_labs'];
        $data['total_filtered_studies'] = $sequence_data['total_filtered_studies'];
        $data['total_filtered_sequences'] = $sequence_data['total_filtered_sequences'];
        $data['filtered_repositories'] = $sequence_data['filtered_repositories'];

        $filtered_repositories_names = array_map(function ($rs) {
            return $rs->name;
        }, $sequence_data['filtered_repositories']);
        $data['filtered_repositories_names'] = implode(', ', $filtered_repositories_names);

        // for bookmarking
        $current_url = $request->fullUrl();
        $data['url'] = $current_url;
        $data['bookmark_id'] = Bookmark::getIdFromURl($current_url, auth()->user()->id);

        // get sequence fields
        $field_list = FieldName::getSequenceFields();
        $data['field_list'] = $field_list;

        // get sequence fields grouped
        $field_list_grouped = FieldName::getSequenceFieldsGrouped();
        $data['field_list_grouped'] = $field_list_grouped;

        // table columns to display
        if (isset($filters['cols'])) {
            $current_columns = explode(',', $filters['cols']);
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

        // keep filters panels open
        $open_filter_panel_list = [];
        if (isset($filters['open_filter_panel_list'])) {
            $open_filter_panel_list = $filters['open_filter_panel_list'];
        }
        $data['open_filter_panel_list'] = $open_filter_panel_list;

        // hidden form fields
        $hidden_fields = [];

        foreach ($filters as $p => $v) {
            if (starts_with($p, 'ir_project_sample_id_list_')) {
                foreach ($v as $sample_id) {
                    $hidden_fields[] = ['name' => $p . '[]', 'value' => $sample_id];
                }
            }
        }
        $hidden_fields[] = ['name' => 'cols', 'value' => $current_columns_str];
        $data['hidden_fields'] = $hidden_fields;
        $data['filters_json'] = json_encode($filters);

        // create copy of filters for display
        $filter_fields = [];
        foreach ($filters as $k => $v) {
            if ($v) {
                if (is_array($v)) {
                    // don't show sample id filters
                    if (! starts_with($k, 'ir_project_sample_id_list_')) {
                        $filter_fields[$k] = implode(', ', $v);
                    }
                } else {
                    $filter_fields[$k] = $v;
                }
            }
        }
        // remove gateway-specific filters
        unset($filter_fields['cols']);
        unset($filter_fields['filters_order']);
        unset($filter_fields['sample_query_id']);
        unset($filter_fields['open_filter_panel_list']);
        $data['filter_fields'] = $filter_fields;

        // for analysis app
        $amazingHistogramGeneratorColorList = [];
        $amazingHistogramGeneratorColorList['1_0_0'] = 'Red';
        $amazingHistogramGeneratorColorList['1_0.5_0'] = 'Orange';
        $amazingHistogramGeneratorColorList['1_0_1'] = 'Pink';
        $amazingHistogramGeneratorColorList['0.6_0.4_0.2'] = 'Brown';
        $data['amazingHistogramGeneratorColorList'] = $amazingHistogramGeneratorColorList;

        // for histogram generator
        $var_list = [];
        $var_list['junction_length'] = __('short.junction_length');
        $var_list['v_call'] = __('short.v_call');
        $var_list['d_call'] = __('short.d_call');
        $var_list['j_call'] = __('short.j_call');
        $var_list = FieldName::convert($var_list, 'ir_id', 'ir_v2');
        $data['var_list'] = $var_list;

        $data['system'] = System::getCurrentSystem(auth()->user()->id);

        // display view
        return view('sequence', $data);
    }

    public function postQuickSearch(Request $request)
    {
        $query_id = Query::saveParams($request->except(['_token']), 'sequences');
        Log::debug('Saving quickSearch params to query id ' . $query_id);

        return redirect('sequences-quick-search?query_id=' . $query_id)->withInput();
    }

    public function quickSearch(Request $request)
    {
        // if "remove one filter" request, generate new query_id and redirect to it
        if ($request->has('remove_filter')) {
            $filters = Query::getParams($request->input('query_id'));
            $filter_to_remove = $request->input('remove_filter');

            unset($filters[$filter_to_remove]);
            $new_filters = $filters;

            $new_query_id = Query::saveParams($new_filters, 'sequences');

            return redirect('sequences-quick-search?query_id=' . $new_query_id);
        }

        $username = auth()->user()->username;

        /*************************************************
        * prepare form data */

        // get data
        $metadata = Sample::metadata($username);

        // cell type
        $cell_type_list = [];
        foreach ($metadata['cell_subset'] as $v) {
            $cell_type_list[$v] = $v;
        }

        // organism
        $subject_organism_list = [];
        $subject_organism_list[''] = 'Any';
        foreach ($metadata['organism'] as $v) {
            $subject_organism_list[$v] = $v;
        }

        // view data
        $data = [];
        $data['cell_type_list'] = $cell_type_list;
        $data['subject_organism_list'] = $subject_organism_list;

        /*************************************************
        * get filtered sequence data and related statistics */

        $query_id = '';
        $filters = [];

        if ($request->has('query_id')) {
            $query_id = $request->input('query_id');
            $filters = Query::getParams($query_id);
        }

        // fill form fields accordingly
        $request->session()->forget('_old_input');
        $request->session()->put('_old_input', $filters);

        $data['query_id'] = $query_id;

        $sample_filters = [];
        if (isset($filters['cell_subset'])) {
            $sample_filters['cell_subset'] = $filters['cell_subset'];
        }
        if (isset($filters['organism'])) {
            $sample_filters['organism'] = $filters['organism'];
        }

        $sequence_filters = [];
        if (isset($filters['junction_aa'])) {
            $sequence_filters['junction_aa'] = $filters['junction_aa'];
        }

        // generate query id for download link
        $sample_id_list = Sample::find_sample_id_list($sample_filters, $username);
        $download_filters = array_merge($sequence_filters, $sample_id_list);

        // add sample_query_id to keep track of sample filters for info file
        $sample_query_id = Query::saveParams($sample_filters, 'samples');
        $download_filters['sample_query_id'] = $sample_query_id;

        $download_query_id = Query::saveParams($download_filters, 'sequences');
        $data['download_query_id'] = $download_query_id;

        $sequence_data = Sequence::full_search($sample_filters, $sequence_filters, $username);

        // log result
        $query_log_id = $request->get('query_log_id');
        if ($query_log_id != null) {
            $query_log = QueryLog::find($query_log_id);
            $query_log->result_size = $sequence_data['total_filtered_sequences'];
            $query_log->save();
        }

        // summary for each REST service
        $rest_service_list = $sequence_data['rs_list'];
        foreach ($rest_service_list as $rs) {
            $summary = $rs['summary'];
        }

        $data['sequence_list'] = $sequence_data['items'];
        $data['sample_list_json'] = json_encode($sequence_data['summary']);
        $data['rest_service_list'] = $rest_service_list;
        $data['rs_list_no_response'] = $sequence_data['rs_list_no_response'];

        // Pass on the summary data from the sequence_data returned.
        $data['total_filtered_samples'] = $sequence_data['total_filtered_samples'];
        $data['total_filtered_repositories'] = $sequence_data['total_filtered_repositories'];
        $data['total_filtered_labs'] = $sequence_data['total_filtered_labs'];
        $data['total_filtered_studies'] = $sequence_data['total_filtered_studies'];
        $data['total_filtered_sequences'] = $sequence_data['total_filtered_sequences'];
        $data['filtered_repositories'] = $sequence_data['filtered_repositories'];

        $filtered_repositories_names = array_map(function ($rs) {
            return $rs->display_name;
        }, $sequence_data['filtered_repositories']);
        $data['filtered_repositories_names'] = implode(', ', $filtered_repositories_names);

        // for bookmarking
        $current_url = $request->fullUrl();
        $data['url'] = $current_url;
        $data['bookmark_id'] = Bookmark::getIdFromURl($current_url, auth()->user()->id);

        // get sequence fields
        $field_list = FieldName::getSequenceFields();
        $data['field_list'] = $field_list;

        // get sequence fields grouped
        $field_list_grouped = FieldName::getSequenceFieldsGrouped();
        $data['field_list_grouped'] = $field_list_grouped;

        // table columns to display
        if (isset($filters['cols'])) {
            $current_columns = explode(',', $filters['cols']);
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

        // hidden form fields
        $hidden_fields = [];

        $hidden_fields[] = ['name' => 'cols', 'value' => $current_columns_str];
        $data['hidden_fields'] = $hidden_fields;
        $data['filters_json'] = json_encode($filters);

        // create copy of current filters for display
        $filter_fields = [];
        foreach ($filters as $k => $v) {
            if ($v) {
                if (is_array($v)) {
                    // don't show sample id filters
                    if (! starts_with($k, 'ir_project_sample_id_list_')) {
                        $filter_fields[$k] = implode(', ', $v);
                    }
                } else {
                    $filter_fields[$k] = $v;
                }
            }
        }
        // remove gateway-specific filters
        unset($filter_fields['cols']);
        unset($filter_fields['filters_order']);
        unset($filter_fields['sample_query_id']);
        unset($filter_fields['open_filter_panel_list']);
        $data['filter_fields'] = $filter_fields;

        // display view
        return view('sequenceQuickSearch', $data);
    }

    public function download(Request $request)
    {
        $query_id = $request->input('query_id');
        $nb_sequences = $request->input('n');
        $time_estimate_max = '24 hours';

        if ($nb_sequences < 500000) {
            $time_estimate_max = '20 min';
        }

        if ($nb_sequences < 100000) {
            $time_estimate_max = '5 min';
        }

        $data = [];
        $data['query_id'] = $query_id;
        $data['time_estimate_max'] = $time_estimate_max;

        // display view
        return view('sequenceDownload', $data);
    }

    public function downloadDirect(Request $request, Response $response)
    {
        $username = auth()->user()->username;

        $query_id = $request->input('query_id');
        if ($query_id) {
            $filters = Query::getParams($query_id);
        } else {
            $filters = $request->all();
        }

        $sample_filter_fields = [];
        if (isset($filters['sample_query_id'])) {
            $sample_query_id = $filters['sample_query_id'];

            // sample filters
            $sample_filters = Query::getParams($sample_query_id);
            $sample_filter_fields = [];
            foreach ($sample_filters as $k => $v) {
                if ($v) {
                    if (is_array($v)) {
                        $sample_filter_fields[$k] = implode(', ', $v);
                    } else {
                        $sample_filter_fields[$k] = $v;
                    }
                }
            }
            // remove gateway-specific params
            unset($sample_filter_fields['open_filter_panel_list']);
        }

        $t = Sequence::sequencesTSV($filters, $username, $request->fullUrl(), $sample_filter_fields);
        $tsvFilePath = $t['public_path'];

        // log result
        $query_log_id = $request->get('query_log_id');
        if ($query_log_id != null) {
            $query_log = QueryLog::find($query_log_id);
            $query_log->result_size = $t['size'];
            $query_log->save();
        }

        if ($request->ajax()) {
            $data = [];

            $data['file_path'] = $tsvFilePath;

            if ($t['is_download_incomplete']) {
                $data['incomplete'] = $t['is_download_incomplete'];
                $data['file_stats'] = $t['file_stats'];
            }

            return response()->json($data);
        } else {
            return redirect($tsvFilePath);
        }
    }
}
