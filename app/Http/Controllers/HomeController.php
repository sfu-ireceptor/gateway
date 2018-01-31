<?php

namespace App\Http\Controllers;

use App\RestService;
use App\SequenceColumnName;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        // get count of available data (sequences, samples)
        $username = auth()->user()->username;
        $metadata = RestService::metadata($username);
        $data = $metadata;

        // get data for graphs and repositories/labs/studies popup
        $query_log_id = $request->get('query_log_id');
        $sample_data = RestService::samples(['ajax' => true], $username, $query_log_id);
        $sample_list = $sample_data['items'];

        $data['sample_list_json'] = json_encode($sample_list);
        $data['rest_service_list'] = $sample_data['rs_list'];

        // cell type
        $cell_type_list = [];
        foreach ($metadata['cell_subset'] as $v) {
            $cell_type_list[$v] = $v;
        }
        $data['cell_type_list'] = $cell_type_list;

        // organism
        $subject_organism_list = [];
        foreach ($metadata['organism'] as $v) {
            $subject_organism_list[$v] = $v;
        }
        $data['subject_organism_list'] = $subject_organism_list;

        // get fields names
        $sequenceColumnNameList = SequenceColumnName::findEnabled();
        $filters_list_all = [];
        foreach ($sequenceColumnNameList as $s) {
            $name = $s['name'];
            $title = $s['title'];
            $filters_list_all[$name] = $title;
        }
        $data['filters_list_all'] = $filters_list_all;

        return view('home', $data);
    }

    public function about()
    {
        return view('about');
    }
}
