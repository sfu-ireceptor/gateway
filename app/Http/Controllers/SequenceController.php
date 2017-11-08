<?php

namespace App\Http\Controllers;

use App\System;
use App\Bookmark;
use App\RestService;
use App\SequenceColumnName;
use Illuminate\Http\Request;

class SequenceController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->all();
        $username = auth()->user()->username;

        // if csv
        if (isset($filters['csv'])) {
            $csvFilePath = RestService::sequencesCSV($filters, $username);

            return redirect($csvFilePath);
        }

        $request->flashExcept('ir_project_sample_id_list');   // keep submitted form values

        // sequence list
        $sequence_data = RestService::sequences_summary($filters, $username);
        // var_dump($sequence_data);die();

        // summary for each REST service
        $rs_list = $sequence_data['rs_list'];
        foreach ($rs_list as $rs) {
            $summary = $rs['summary'];
            // var_dump($summary);die();
        }

        $data = [];
        $data['sequence_list'] = $sequence_data['items'];
        $data['sample_list_json'] = json_encode($sequence_data['summary']);
        $data['rs_list'] = $rs_list;
        // $data['total_sequences'] = $sequence_data['total'];
        // $data['filters'] = $sequence_data['filters'];
        // $data['totalRepositories'] = $sequence_data['totalRepositories'];
        // $data['totalLabs'] = $sequence_data['totalLabs'];
        // $data['totalStudies'] = $sequence_data['totalStudies'];
        // $data['totalSamples'] = $sequence_data['totalSamples'];
        // $data['totalSequences'] = $sequence_data['totalSequences'];
        // $data['nFilteredSequences'] = $sequence_data['total'];

        // for bookmarking
        $current_url = $request->fullUrl();
        $data['url'] = $current_url;
        $data['bookmark_id'] = Bookmark::getIdFromURl($current_url, auth()->user()->id);

        // columns to display
        $defaultSequenceColumns = [9, 13, 17, 65, 113];
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
        $defaultFiltersListIds = [9, 13, 17, 65, 113];
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

        $data['filters_json'] = json_encode($filters);
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
}
