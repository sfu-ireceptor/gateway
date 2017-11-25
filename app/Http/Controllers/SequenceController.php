<?php

namespace App\Http\Controllers;

use App\Query;
use App\System;
use App\Bookmark;
use App\RestService;
use App\SequenceColumnName;
use Illuminate\Http\Request;

class SequenceController extends Controller
{
    public function postIndex(Request $request)
    {
        $query_id = Query::saveParams($request->except(['_token']), 'sequences');

        return redirect('sequences?query_id=' . $query_id)->withInput();
    }

    public function index(Request $request)
    {
        $username = auth()->user()->username;
        $data = [];


        $query_id = $request->input('query_id');
        if ($query_id) {
            $filters = Query::getParams($query_id);
            if (! $request->session()->has('_old_input')) {
                $request->session()->put('_old_input', $filters);
            }

            // generate query_id for "Clear Filters" button
            $no_filters_params = [];
            foreach ($filters as $name => $value) {
                if (starts_with($name, 'ir_project_sample_id_list_')) {
                    $no_filters_params[$name] = $value;
                }
            }
            $data['no_filters_query_id'] = Query::saveParams($no_filters_params, 'sequences');
        } else {
            $filters = $request->all();
            $request->session()->forget('_old_input');
        }

        // if csv
        if (isset($filters['csv'])) {
            $csvFilePath = RestService::sequencesCSV($filters, $username);

            return redirect($csvFilePath);
        }

        // $request->flashExcept('ir_project_sample_id_list');   // keep submitted form values

        // sequence list
        $sequence_data = RestService::sequences_summary($filters, $username);
        // var_dump($sequence_data);die();

        // summary for each REST service
        $rs_list = $sequence_data['rs_list'];
        foreach ($rs_list as $rs) {
            $summary = $rs['summary'];
            // var_dump($summary);die();
        }

        $data['sequence_list'] = $sequence_data['items'];
        $data['sample_list_json'] = json_encode($sequence_data['summary']);
        $data['rs_list'] = $rs_list;

        // Pass on the summary data from the sequence_data returned.
        $data['total_filtered_samples'] = $sequence_data['total_filtered_samples'];
        $data['total_filtered_repositories'] = $sequence_data['total_filtered_repositories'];
        $data['total_filtered_labs'] = $sequence_data['total_filtered_labs'];
        $data['total_filtered_studies'] = $sequence_data['total_filtered_studies'];
        $data['total_filtered_sequences'] = $sequence_data['total_filtered_sequences'];
        $data['filtered_repositories'] = $sequence_data['filtered_repositories'];
        $data['filter_fields'] = $sequence_data['filter_fields'];

        $filtered_repositories_names = array_map(function ($rs) {
            return $rs->name;
        }, $sequence_data['filtered_repositories']);
        $data['filtered_repositories_names'] = implode(', ', $filtered_repositories_names);

        // for bookmarking
        $current_url = $request->fullUrl();
        $data['url'] = $current_url;
        $data['bookmark_id'] = Bookmark::getIdFromURl($current_url, auth()->user()->id);

        // columns to display
        $defaultSequenceColumns = [1, 2, 3, 4, 5];
        if (isset($filters['cols'])) {
            $currentSequenceColumns = explode('_', $filters['cols']);
        } else {
            $currentSequenceColumns = $defaultSequenceColumns;
        }
        $data['current_sequence_columns'] = $currentSequenceColumns;
        $data['sequence_column_name_list'] = SequenceColumnName::findEnabled();
        // foreach ($data['sequence_column_name_list'] as $o) {
        //     echo $o->id . '-' . $o->title . '<br />';
        // }
        // die();
        $currentSequenceColumnsStr = implode('_', $currentSequenceColumns);

        // filters
        $defaultFiltersListIds = [1, 2, 3, 4, 5];
        $filtersListIds = [];
        if (isset($filters['filters_order'])) {
            $filtersListIds = explode('_', $filters['filters_order']);
        } else {
            $filtersListIds = $defaultFiltersListIds;
        }

        $filtersListDisplayed = [];
        $filtersListSelect = [];

        $sequenceColumnNameList = SequenceColumnName::findEnabled();

        // create array for select field
        foreach ($sequenceColumnNameList as $s) {
            $name = $s['name'];
            $title = $s['title'];
            if (! in_array($s['id'], $filtersListIds)) {
                $filtersListSelect[$name] = $title;
            }
        }

        // create array to display fields in the right order
        foreach ($filtersListIds as $filterId) {
            $field = SequenceColumnName::find($filterId);
            $name = $field['name'];
            $title = $field['title'];
            $filtersListDisplayed[$name] = $title;
        }

        $data['filters_list'] = $filtersListDisplayed;
        $data['filters_list_select'] = $filtersListSelect;
        $data['filters_list_all'] = array_merge($filtersListDisplayed, $filtersListSelect);
        $currentFiltersListIdsStr = implode('_', $filtersListIds);

        // hidden form fields
        $hidden_fields = [];

        foreach ($filters as $p => $v) {
            if (starts_with($p, 'ir_project_sample_id_list_')) {
                foreach ($v as $sample_id) {
                    $hidden_fields[] = ['name' => $p . '[]', 'value' => $sample_id];
                }
            }
        }
        $hidden_fields[] = ['name' => 'cols', 'value' => $currentSequenceColumnsStr];
        $hidden_fields[] = ['name' => 'filters_order', 'value' => $currentFiltersListIdsStr];
        $data['hidden_fields'] = $hidden_fields;
        $data['filters_json'] = json_encode($filters);

        // build URL without sequence filters but keeping samples selection
        $sequence_filters = [];
        $sequence_column_name_list = SequenceColumnName::findEnabled();
        foreach ($sequence_column_name_list as $scn) {
            $sequence_filters[] = $scn['name'];
        }
        $filters_without_sequence_filters = array_except($filters, $sequence_filters);
        $data['no_filters_url'] = '/sequences?' . http_build_query($filters_without_sequence_filters);

        // for analysis app
        $amazingHistogramGeneratorColorList = [];
        $amazingHistogramGeneratorColorList['1_0_0'] = 'Red';
        $amazingHistogramGeneratorColorList['1_0.5_0'] = 'Orange';
        $amazingHistogramGeneratorColorList['1_0_1'] = 'Pink';
        $amazingHistogramGeneratorColorList['0.6_0.4_0.2'] = 'Brown';
        $data['amazingHistogramGeneratorColorList'] = $amazingHistogramGeneratorColorList;

        // for histogram generator
        $var_list = [];
        $var_list['cdr3_length'] = 'CDR3 Length';
        $data['var_list'] = $var_list;

        $data['system'] = System::getCurrentSystem(auth()->user()->id);

        // display view
        return view('sequence', $data);

        // for later: display current project/sample names
    // <p>
    //     @foreach ($sample_list as $sample)
    //         <strong>{{ $sample->project_name }}</strong>
    //         -
    //         <strong>{{ $sample->sample_name }}</strong>
    //         <br />
    //     @endforeach
    // </p>
    }

    public function quickSearch(Request $request)
    {
        $username = auth()->user()->username;

        /*************************************************
        * prepare form data */

        // get data
        $metadata = RestService::metadata($username);

        // cell type
        $cell_type_list = [];
        foreach ($metadata['cell_subset'] as $v) {
            $cell_type_list[$v] = $v;
        }

        // data
        $data = [];
        $data['cell_type_list'] = $cell_type_list;

        /*************************************************
        * get filtered sequence data and related statistics */

        $filters = $request->all();

        // // !! just for quick testing
        // $filters['cell_subset'] = ['B cell'];
        // $filters['junction_aa'] = 'CAHRRVGSSSDWNGGDYDFW';

        $sample_filters = [];
        if (isset($filters['cell_subset'])) {
            $sample_filters['cell_subset'] = $filters['cell_subset'];
        }
        if (isset($filters['organism'])) {
            $sample_filters['organism'] = $filters['organism'];
        }

        // $sample_filters['organism'] = $filters['organism'];

        $sequence_filters = [];
        if (isset($filters['junction_aa'])) {
            $sequence_filters['junction_aa'] = $filters['junction_aa'];
        }
        $sequence_data = RestService::search($sample_filters, $sequence_filters, $username);
        // dd($sequence_data);

        // summary for each REST service
        $rs_list = $sequence_data['rs_list'];
        foreach ($rs_list as $rs) {
            $summary = $rs['summary'];
            // var_dump($summary);die();
        }

        $data['sequence_list'] = $sequence_data['items'];
        $data['sample_list_json'] = json_encode($sequence_data['summary']);
        $data['rs_list'] = $rs_list;

        // Pass on the summary data from the sequence_data returned.
        $data['total_filtered_samples'] = $sequence_data['total_filtered_samples'];
        $data['total_filtered_repositories'] = $sequence_data['total_filtered_repositories'];
        $data['total_filtered_labs'] = $sequence_data['total_filtered_labs'];
        $data['total_filtered_studies'] = $sequence_data['total_filtered_studies'];
        $data['total_filtered_sequences'] = $sequence_data['total_filtered_sequences'];
        $data['filtered_repositories'] = $sequence_data['filtered_repositories'];
        $data['filter_fields'] = $sequence_data['filter_fields'];

        $filtered_repositories_names = array_map(function ($rs) {
            return $rs->name;
        }, $sequence_data['filtered_repositories']);
        $data['filtered_repositories_names'] = implode(', ', $filtered_repositories_names);

        // for bookmarking
        $current_url = $request->fullUrl();
        $data['url'] = $current_url;
        $data['bookmark_id'] = Bookmark::getIdFromURl($current_url, auth()->user()->id);

        // columns to display
        $defaultSequenceColumns = [1, 2, 3, 4, 5];
        if (isset($filters['cols'])) {
            $currentSequenceColumns = explode('_', $filters['cols']);
        } else {
            $currentSequenceColumns = $defaultSequenceColumns;
        }
        $data['current_sequence_columns'] = $currentSequenceColumns;
        $data['sequence_column_name_list'] = SequenceColumnName::findEnabled();
        // foreach ($data['sequence_column_name_list'] as $o) {
        //     echo $o->id . '-' . $o->title . '<br />';
        // }
        // die();
        $currentSequenceColumnsStr = implode('_', $currentSequenceColumns);

        // filters
        $defaultFiltersListIds = [1, 2, 3, 4, 5];
        $filtersListIds = [];
        if (isset($filters['filters_order'])) {
            $filtersListIds = explode('_', $filters['filters_order']);
        } else {
            $filtersListIds = $defaultFiltersListIds;
        }

        $filtersListDisplayed = [];
        $filtersListSelect = [];

        $sequenceColumnNameList = SequenceColumnName::findEnabled();

        // create array for select field
        foreach ($sequenceColumnNameList as $s) {
            $name = $s['name'];
            $title = $s['title'];
            if (! in_array($s['id'], $filtersListIds)) {
                $filtersListSelect[$name] = $title;
            }
        }

        // create array to display fields in the right order
        foreach ($filtersListIds as $filterId) {
            $field = SequenceColumnName::find($filterId);
            $name = $field['name'];
            $title = $field['title'];
            $filtersListDisplayed[$name] = $title;
        }

        $data['filters_list'] = $filtersListDisplayed;
        $data['filters_list_select'] = $filtersListSelect;
        $data['filters_list_all'] = array_merge($filtersListDisplayed, $filtersListSelect);
        $currentFiltersListIdsStr = implode('_', $filtersListIds);

        // hidden form fields
        $hidden_fields = [];

        $hidden_fields[] = ['name' => 'cols', 'value' => $currentSequenceColumnsStr];
        $data['hidden_fields'] = $hidden_fields;
        $data['filters_json'] = json_encode($filters);

        // display view
        return view('sequenceQuickSearch', $data);
    }
}
