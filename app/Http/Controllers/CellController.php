<?php

namespace App\Http\Controllers;

use App\Bookmark;
use App\Download;
use App\FieldName;
use App\QueryLog;
use App\Sample;
use App\Sequence;
use App\SequenceCell;
use App\System;
use App\Tapis;
use Facades\App\Query;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CellController extends Controller
{
    public function __construct()
    {
        // default timeout for cell requests
        $timeout = config('ireceptor.gateway_request_timeout');
        set_time_limit($timeout);
    }

    // when form is submitted (POST), generate query id and redirect (GET)
    public function postIndex(Request $request)
    {
        $query_id = Query::saveParams($request->except(['_token']), 'cells');

        return redirect('cells?query_id=' . $query_id)->withInput();
    }

    public function index(Request $request)
    {
        /*************************************************
        * Immediate redirects */

        // if request without query id, generate query id and redirect
        if (! $request->has('query_id')) {
            $query_id = Query::saveParams($request->except(['_token']), 'sequences');

            return redirect('cells?query_id=' . $query_id)->withInput();
        }

        // if "remove filter" request, generate new query_id and redirect
        if ($request->has('remove_filter')) {
            return self::removeFilter($request);
        }

        /*************************************************
        * Get cell data */

        // parameters
        $query_id = $request->input('query_id');
        $filters = Query::getParams($query_id);
        $username = auth()->user()->username;

        // allow only Cell filters, or only GEX filters, based on currently opened panel
        if (isset($filters['open_filter_panel_list'])) {
            $open_filter_panel_list = $filters['open_filter_panel_list'];
            if ($open_filter_panel_list[0] == 0) {
                unset($filters['property_expression']);
                unset($filters['value_expression']);
            } else {
                unset($filters['expression_study_method_cell']);
                unset($filters['virtual_pairing_cell']);
            }
        }

        // retrieve data
        $cell_data = SequenceCell::summary($filters, $username);

        // store data size in user query log
        $query_log_id = $request->get('query_log_id');
        $query_log = QueryLog::find($query_log_id);
        if ($query_log != null) {
            $query_log->result_size = $cell_data['total_filtered_cells'];
            $query_log->save();
        }

        /*************************************************
        * Prepare view data */

        // sequence data
        $data = [];

        $data['cell_list'] = $cell_data['items'];

        // Fields we want to graph. The UI/blade expects six fields
        $charts_fields = ['study_title', 'subject_id', 'sample_id', 'disease_diagnosis_id', 'tissue_id', 'cell_subset'];
        // Mapping of fields to display as labels on the graph for those that need
        // mappings. These are usually required for ontology fields where we want
        // to aggregate on the ontology ID but display the ontology label.
        $field_map = ['disease_diagnosis_id' => 'disease_diagnosis',
            'tissue_id' => 'tissue', ];

        $data['charts_data'] = Sample::generateChartsData($cell_data['summary'], $charts_fields, $field_map, 'ir_filtered_cell_count');

        $data['rest_service_list'] = $cell_data['rs_list'];
        $data['rest_service_list_no_response'] = $cell_data['rs_list_no_response'];
        $data['rest_service_list_no_response_timeout'] = $cell_data['rs_list_no_response_timeout'];
        $data['rest_service_list_no_response_error'] = $cell_data['rs_list_no_response_error'];

        // Pass on the summary data from the cell_data returned.
        $data['total_filtered_samples'] = $cell_data['total_filtered_samples'];
        $data['total_filtered_repositories'] = $cell_data['total_filtered_repositories'];
        $data['total_filtered_labs'] = $cell_data['total_filtered_labs'];
        $data['total_filtered_studies'] = $cell_data['total_filtered_studies'];
        $data['total_filtered_cells'] = $cell_data['total_filtered_cells'];
        $data['filtered_repositories'] = $cell_data['filtered_repositories'];

        // populate form fields if needed
        $request->session()->forget('_old_input');
        $request->session()->put('_old_input', $filters);

        $data['query_id'] = $query_id;

        // sample query id
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
            unset($sample_filter_fields['page']);
            unset($sample_filter_fields['sort_column']);
            unset($sample_filter_fields['sort_order']);
            unset($sample_filter_fields['extra_field']);
        }
        $data['sample_filter_fields'] = $sample_filter_fields;

        // functional
        $functional_list = [];
        $functional_list[''] = 'Any';
        $functional_list['true'] = 'Yes';
        $functional_list['false'] = 'No';

        $data['functional_list'] = $functional_list;

        // for bookmarking
        $current_url = $request->fullUrl();
        $data['url'] = $current_url;
        $data['bookmark_id'] = Bookmark::getIdFromURl($current_url, auth()->user()->id);

        // get cell fields
        $field_list = FieldName::getCellFields();
        $data['field_list'] = $field_list;

        // get cell fields grouped
        $field_list_grouped = FieldName::getCellFieldsGrouped();
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

        // Get information about all of the Apps for the AIRR "Cell" object
        $tapis = new Tapis;
        $appTemplates = $tapis->getAppTemplates('Cell');
        $app_list = [];

        // Store the normal job contorl parameters for the UI. The same parameters are used
        // by all Apps.
        $job_parameter_list = $tapis->getJobParameters();

        // For each app, set up the info required by the UI for the App parameters.
        foreach ($appTemplates as $app_tag => $app_info) {
            $app_config = $app_info['config'];
            $app_ui_info = [];
            Log::debug('Processing app ' . $app_tag);
            Log::debug('App config = ' . json_encode($app_config));
            // Process the parameters.
            $parameter_list = [];
            //foreach ($app_config['parameters'] as $parameter_info) {
            foreach ($app_config['jobAttributes']['parameterSet']['appArgs'] as $parameter_info) {
                // We only want the visible parameters to be visible. The
                // UI uses the Tapis ID as a label and the Tapis paramenter
                // "label" as the human readable name of the parameter.
                //if ($parameter_info['value']['visible']) {
                if ($parameter_info['inputMode'] != 'FIXED') {
                    $parameter = [];
                    Log::debug('   Processing parameter - ' . $parameter_info['name']);
                    Log::debug('   Processing parameter - ' . $parameter_info['notes']['label']);
                    $parameter['label'] = $parameter_info['notes']['label'];
                    $parameter['name'] = $parameter_info['name'];
                    $parameter['description'] = $parameter_info['description'];
                    $parameter['type'] = 'string';
                    $parameter['default'] = $parameter_info['arg'];
                    $parameter_list[$parameter_info['name']] = $parameter;

                //Log::debug('   Processing parameter ' . $parameter_info['id']);
                //$parameter['label'] = $parameter_info['id'];
                //$parameter['name'] = $parameter_info['details']['label'];
                //$parameter['description'] = $parameter_info['details']['description'];
                //$parameter['type'] = $parameter_info['value']['type'];
                //$parameter['default'] = $parameter_info['value']['default'];
                //$parameter_list[$parameter_info['id']] = $parameter;
                } else {
                    Log::debug('   Not displaying invisible parameter ' . $parameter_info['id']);
                }
            }

            // The name of the App is the Tapis App label. We pass the UI the short
            // and long descriptions as well . The UI ID and tag are the Tapis ID.
            $app_ui_info['name'] = $app_config['description'];
            $app_ui_info['description'] = $app_config['description'];
            $app_ui_info['info'] = $app_config['jobAttributes']['description'];
            //$app_ui_info['name'] = $app_config['label'];
            //$app_ui_info['description'] = $app_config['shortDescription'];
            //$app_ui_info['info'] = $app_config['longDescription'];
            $app_ui_info['parameter_list'] = $parameter_list;
            $app_ui_info['job_parameter_list'] = $job_parameter_list;
            $app_ui_info['app_id'] = $app_tag;
            $app_ui_info['app_tag'] = $app_tag;

            // Save the info in the app list given to the UI.
            $app_list[$app_tag] = $app_ui_info;
        }
        // Log::debug($app_list);

        // Add the app list to the data returned to the View.
        $data['app_list'] = $app_list;

        $data['system'] = System::getCurrentSystem(auth()->user()->id);

        // download time estimate
        $data['download_time_estimate'] = $this->timeEstimate($data['total_filtered_cells']);

        // display view
        return view('cell', $data);
    }

    public function timeEstimate($nb_sequences)
    {
        $time_estimate_max = '24 hours';

        if ($nb_sequences < 500000) {
            $time_estimate_max = '20 min';
        }

        if ($nb_sequences < 100000) {
            $time_estimate_max = '';
        }

        return $time_estimate_max;
    }

    public function download(Request $request)
    {
        $query_id = $request->input('query_id');
        $username = auth()->user()->username;

        $page = $request->input('page');
        $page_url = route($page, ['query_id' => $query_id], false);

        $nb_cells = $request->input('n');

        Download::start_cell_download($query_id, $username, $page_url, $nb_cells, 'cell');

        return redirect('downloads')->with('download_page', $page_url);
    }

    public function removeFilter(Request $request)
    {
        $filters = Query::getParams($request->input('query_id'));

        $filter_to_remove = $request->input('remove_filter');
        if ($filter_to_remove == 'all') {
            // keep only sample/columns filters
            $new_filters = [];
            foreach ($filters as $name => $value) {
                if (starts_with($name, 'ir_project_sample_id_list_') || $name == 'sample_query_id' || $name == 'cols') {
                    $new_filters[$name] = $value;
                }
            }
        } else {
            // remove only that one filter
            unset($filters[$filter_to_remove]);
            $new_filters = $filters;
        }

        $new_query_id = Query::saveParams($new_filters, 'sequences');

        $uri = $request->route()->uri;

        return redirect($uri . '?query_id=' . $new_query_id);
    }
}
