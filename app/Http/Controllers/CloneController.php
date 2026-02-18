<?php

namespace App\Http\Controllers;

use App\Bookmark;
use App\Clones;
use App\Download;
use App\FieldName;
use App\QueryLog;
use App\Sample;
use App\System;
use App\Tapis;
use Facades\App\Query;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CloneController extends Controller
{
    public function __construct()
    {
        // default timeout for clone requests
        $timeout = config('ireceptor.gateway_request_timeout');
        set_time_limit($timeout);
    }

    // when form is submitted (POST), generate query id and redirect (GET)
    public function postIndex(Request $request)
    {
        $query_id = Query::saveParams($request->except(['_token']), 'clones');

        return redirect('clones?query_id=' . $query_id)->withInput();
    }

    public function index(Request $request)
    {
        /*************************************************
        * Immediate redirects */

        // if request without query id, generate query id and redirect
        if (! $request->has('query_id')) {
            $query_id = Query::saveParams($request->except(['_token']), 'clones');

            return redirect('clones?query_id=' . $query_id)->withInput();
        }

        // if "remove filter" request, generate new query_id and redirect
        if ($request->has('remove_filter')) {
            return self::removeFilter($request);
        }
        /*************************************************
        * Check access control */

        // Get query_id parameter from the query
        $query_id = $request->input('query_id');
        Log::debug('CloneController::index - query_id = ' . $query_id);

        // User object for current user
        $user = Auth::user();

        // Check to see if the user is has access to a clones resource
        // with the query_id they are requesting.
        // This should not happen in normal functioning of the Gateway, but
        // is necessary to prevent users changing the query_id in the URL.
        if ($query_id != null && ! $user->hasAccessQueryID('clones', $query_id)) {
            abort(401, 'Not authorized.');
        }

        /*************************************************
        * Get clone data */

        // parameters
        $query_id = $request->input('query_id');
        $filters = Query::getParams($query_id);
        $username = auth()->user()->username;

        // retrieve data
        $clone_data = Clones::summary($filters, $username);
        // dd($clone_data);

        // store data size in user query log
        $query_log_id = $request->get('query_log_id');
        $query_log = QueryLog::find($query_log_id);
        if ($query_log != null) {
            $query_log->result_size = $clone_data['total_filtered_objects'];
            $query_log->save();
        }

        /*************************************************
        * Prepare view data */

        // clone data
        $data = [];

        $data['clone_list'] = $clone_data['items'];

        // Fields we want to graph. The UI/blade expects six fields
        $charts_fields = ['study_title', 'subject_id', 'sample_id', 'disease_diagnosis_id', 'tissue_id', 'pcr_target_locus'];
        // Mapping of fields to display as labels on the graph for those that need
        // mappings. These are usually required for ontology fields where we want
        // to aggregate on the ontology ID but display the ontology label.
        $field_map = ['disease_diagnosis_id' => 'disease_diagnosis',
            'tissue_id' => 'tissue', ];

        $data['charts_data'] = Sample::generateChartsData($clone_data['summary'], $charts_fields, $field_map, 'ir_filtered_clone_count');

        $data['rest_service_list'] = $clone_data['rs_list'];
        $data['rest_service_list_clones'] = $clone_data['rs_list'];
        $data['rest_service_list_no_response'] = $clone_data['rs_list_no_response'];
        $data['rest_service_list_no_response_timeout'] = $clone_data['rs_list_no_response_timeout'];
        $data['rest_service_list_no_response_error'] = $clone_data['rs_list_no_response_error'];

        // Pass on the summary data from the clone_data returned.
        $data['total_filtered_samples'] = $clone_data['total_filtered_samples'];
        $data['total_filtered_repositories'] = $clone_data['total_filtered_repositories'];
        $data['total_filtered_labs'] = $clone_data['total_filtered_labs'];
        $data['total_filtered_studies'] = $clone_data['total_filtered_studies'];
        $data['total_filtered_objects'] = $clone_data['total_filtered_objects'];
        $data['filtered_repositories'] = $clone_data['filtered_repositories'];

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

        // get clone fields
        $field_list = FieldName::getCloneFields();
        $data['field_list'] = $field_list;

        // get clone fields grouped
        $field_list_grouped = FieldName::getCloneFieldsGrouped();
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

        // Get information about all of the Apps for the AIRR "Clone" object
        $tapis = new Tapis;
        $appTemplates = $tapis->getAppTemplates('Clone');
        $data['max_job_time_secs'] = $tapis->maxRunTimeMinutes() * 60;

        $app_list = [];

        // Store the normal job contorl parameters for the UI. The same parameters are used
        // by all Apps.
        $job_parameter_list = $tapis->getJobParameters();

        // For each app, set up the info required by the UI for the App parameters.
        foreach ($appTemplates as $app_tag => $app_info) {
            $app_config = $app_info['config'];
            $app_ui_info = [];
            Log::debug('CloneController::index - Processing app ' . $app_tag);
            // Process the parameters.
            $parameter_list = [];
            foreach ($app_config['jobAttributes']['parameterSet']['appArgs'] as $parameter_info) {
                //foreach ($app_config['parameters'] as $parameter_info) {
                // We only want the visible parameters to be visible. The
                // UI uses the Tapis ID as a label and the Tapis paramenter
                // "label" as the human readable name of the parameter.
                if ($parameter_info['inputMode'] != 'FIXED') {
                    $parameter = [];
                    Log::debug('CloneController::index -    Processing parameter - ' . $parameter_info['name']);
                    $parameter['label'] = $parameter_info['notes']['label'];
                    $parameter['name'] = $parameter_info['name'];
                    $parameter['description'] = $parameter_info['description'];
                    $parameter['type'] = 'string';
                    $parameter['default'] = $parameter_info['arg'];
                    $parameter_list[$parameter_info['name']] = $parameter;
                } else {
                    Log::debug('CloneController::index -    Not displaying invisible parameter ' . $parameter_info['name']);
                }
            }

            // The name of the App is the Tapis App label. We pass the UI the short
            // and long descriptions as well . The UI ID and tag are the Tapis ID.
            $app_ui_info['name'] = $app_config['description'];
            $app_ui_info['description'] = $app_config['description'];
            $app_ui_info['info'] = $app_config['jobAttributes']['description'];

            $app_ui_info['parameter_list'] = $parameter_list;
            $app_ui_info['job_parameter_list'] = $job_parameter_list;
            $app_ui_info['app_id'] = $app_tag;
            $app_ui_info['app_tag'] = $app_tag;
            $app_ui_info['runnable'] = true;
            $app_ui_info['runnable_comment'] = '';
            $app_ui_info['required_time_secs'] = 0; // Unknown by default

            // Get the required memory depending on whether the App proceses data per
            // repertoire or in total
            $required_memory = 0;
            $num_objects = 0;
            $added_string = '';
            // Required is bytes per unit times the number of rearrangements.
            if (array_key_exists('memory_byte_per_unit_total', $app_info)) {
                $num_objects = $data['total_filtered_objects'];
                $required_memory = $num_objects * $app_info['memory_byte_per_unit_total'];
            }
            // Required is bytes per unit times the number of rearrangements in the
            // largest repertoire.
            if (array_key_exists('memory_byte_per_unit_repertoire', $app_info)) {
                // Get the number of rearrangements in the larges repertoire
                $repertoire_objects = 0;
                foreach ($clone_data['summary'] as $sample) {
                    if (property_exists($sample, 'ir_filtered_clone_count') &&
                        $sample->ir_filtered_clone_count > $repertoire_objects) {
                        $repertoire_objects = $sample->ir_filtered_clone_count;
                    }
                }
                // Required is bytes per unit times number of rearrangements in the
                // largest repertoire.
                $required_repertoire_memory = $repertoire_objects * $app_info['memory_byte_per_unit_repertoire'];
                if ($required_repertoire_memory > $required_memory) {
                    $required_memory = $required_repertoire_memory;
                    $num_objects = $repertoire_objects;
                    $added_string = ' (the largest repertoire)';
                }
            }

            // Get the node memory
            $node_memory = $tapis->memoryMBPerNode() * 1024 * 1024;

            // If required memory is more than node memory, disable the app and
            // generate an error message.
            if ($required_memory > $node_memory) {
                Log::debug('CloneController::index -    Memory exceeded');
                Log::debug('CloneController::index -       Required memory = ' . human_filesize($required_memory));
                Log::debug('CloneController::index -       Node memory = ' . human_filesize($node_memory));
                $app_ui_info['runnable'] = false;
                $app_ui_info['runnable_comment'] = 'Unable to run Analysis Job. It is estmated that "' . $app_ui_info['name'] . '" will require ' . human_filesize($required_memory) . ' of memory to process ' . human_number($num_objects) . ' clones' . $added_string . '. Compute nodes are limited to ' . human_filesize($node_memory) . ' of memory.';
            }

            // If we have a time per unit, make sure it will fit in the job runtime.
            if (array_key_exists('time_secs_per_million', $app_info)) {
                // Get the allowed run time
                $job_runtime_secs = $tapis->maxRunTimeMinutes() * 60;
                // Get the number of objects
                $num_objects = $data['total_filtered_objects'];
                // Get the required time based on the apps ms performance per unit
                $required_time_secs = ($num_objects / 1000000) * $app_info['time_secs_per_million'];
                $app_ui_info['required_time_secs'] = $required_time_secs;
                // If requried is greater than run time, disable the app.
                if ($required_time_secs > $job_runtime_secs) {
                    Log::debug('CloneController::index -    Run time exceeded');
                    Log::debug('CloneController::index -       Required run time (s) = ' . human_number($required_time_secs));
                    Log::debug('CloneController::index -       Max run time (s) =  ' . human_number($job_runtime_secs));
                    $app_ui_info['runnable'] = false;
                    $error_string = 'It is estimated that "' . $app_ui_info['name'] . '" will require ' . secondsToTime($required_time_secs, 2) . ' to process ' . human_number($num_objects) . ' clones. Current maximum job run time is ' . secondsToTime($tapis->maxRunTimeMinutes() * 60) . '. Please limit the amount of data used for this analysis.';
                    // If we have a comment already, then add to it, otherwise generate new comment.
                    if (strlen($app_ui_info['runnable_comment']) > 0) {
                        $app_ui_info['runnable_comment'] = $app_ui_info['runnable_comment'] . ' ' . $error_string;
                    } else {
                        $app_ui_info['runnable_comment'] = 'Unable to run Analysis Job. ' . $error_string;
                    }
                }
            }

            // Check the field requirements for the app.
            if (array_key_exists('requirements', $app_info) && array_key_exists('Fields', $app_info['requirements']) && count($app_info['requirements']['Fields']) > 0) {
                foreach ($app_info['requirements']['Fields'] as $field => $value_array) {
                    Log::debug('CloneController::index -    checking requirement ' . $field . ' = ' . json_encode($value_array));
                    // For each sample being processed, make sure the field values are valid.
                    foreach ($clone_data['summary'] as $sample) {
                        $error_string = '';
                        $got_error = false;
                        if (property_exists($sample, $field)) {
                            foreach ($value_array as $value) {
                                // If the property exists and is a mismatch, disable app
                                if ((is_array($sample->$field) && ! in_array($value, $sample->$field)) || (! is_array($sample->$field) && $value != $sample->$field)) {
                                    Log::debug('CloneController::index -        Requirement field is not in sample.');
                                    $got_error = true;
                                    $app_ui_info['runnable'] = false;
                                    $error_string = 'A required value (one of ' . json_encode($value_array) . ') is missing from the "' . $field . '" field in one of the repertoires. Please filter the data so that all repertoires have one of the following values (' . json_encode($value_array) . ') in the "' . $field . '" field.';
                                }
                            }
                        } else {
                            // If the property doesn't exist, disable the app
                            $got_error = true;
                            $app_ui_info['runnable'] = false;
                            $error_string = 'A required value is missing from the "' . $field . '" field in one of the repertoires. Please filter the data so that all repertoires have one of the following values (' . json_encode($value) . ') in the "' . $field . '" field.';
                        }
                        // If we have a comment already, then add to it, otherwise generate new comment.
                        if (strlen($app_ui_info['runnable_comment']) > 0) {
                            $app_ui_info['runnable_comment'] = $app_ui_info['runnable_comment'] . ' ' . $error_string;
                        } else {
                            $app_ui_info['runnable_comment'] = 'Unable to run Analysis Job. ' . $error_string;
                        }

                        // If we have already processed this error for a repertoire, don't bother processing it
                        // again for other repertoires.
                        if ($got_error) {
                            break;
                        }
                    }
                }
            }

            // Save the info in the app list given to the UI.
            $app_list[$app_tag] = $app_ui_info;
        }

        // Add the app list to the data returned to the View.
        $data['app_list'] = $app_list;

        $data['system'] = System::getCurrentSystem(auth()->user()->id);

        // download time estimate
        $data['download_time_estimate'] = $this->timeEstimate($data['total_filtered_objects']);

        // display view
        return view('clone', $data);
    }

    public function timeEstimate($nb_clones)
    {
        $time_estimate_max = '24 hours';

        if ($nb_clones < 500000) {
            $time_estimate_max = '20 min';
        }

        if ($nb_clones < 100000) {
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

        $nb_clones = $request->input('n');

        Download::start_clone_download($query_id, $username, $page_url, $nb_clones);

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

        $new_query_id = Query::saveParams($new_filters, 'clones');

        $uri = $request->route()->uri;

        return redirect($uri . '?query_id=' . $new_query_id);
    }
}
