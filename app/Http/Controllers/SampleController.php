<?php

namespace App\Http\Controllers;

use App\Query;
use App\RestService;
use Illuminate\Http\Request;

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

        /*************************************************
        * prepare form data */

        // get data
        $metadata = RestService::metadata($username);

        // gender
        $subject_gender_list = [];
        $subject_gender_list[''] = '';
        foreach ($metadata['sex'] as $v) {
            $subject_gender_list[$v] = $v;
        }

        // ethnicity
        $subject_ethnicity_list = [];
        $subject_ethnicity_list[''] = '';
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
        * get filtered sample list and related statistics */

        $query_id = $request->input('query_id');
        if ($query_id) {
            $params = Query::getParams($query_id);
            if ( ! $request->session()->has('_old_input')) {
                $request->session()->put('_old_input', $params);
            }
        } else {
            $params = $request->all();
            $request->session()->forget('_old_input');
        }
        $sample_data = RestService::samples($params, $username);

        $data['sample_list'] = $sample_data['items'];
        $data['rest_service_list'] = $sample_data['rs_list'];
        $data['sample_list_json'] = json_encode($sample_data['items']);

        $data['total_filtered_repositories'] = $sample_data['total_filtered_repositories'];
        $data['total_filtered_labs'] = $sample_data['total_filtered_labs'];
        $data['total_filtered_studies'] = $sample_data['total_filtered_studies'];
        $data['total_filtered_samples'] = $sample_data['total_filtered_samples'];
        $data['total_filtered_sequences'] = $sample_data['total_filtered_sequences'];

        $filtered_repositories_names = array_map(function ($rs) {
            return $rs->name;
        }, $sample_data['filtered_repositories']);
        $data['filtered_repositories_names'] = implode(', ', $filtered_repositories_names);

        $data['filter_fields'] = $sample_data['filter_fields'];

        return view('sample', $data);
    }

    public function stats(Request $request)
    {
        // get sample list
        $params = $request->all();
        $params['ajax'] = true;

        $sample_data = RestService::samples($params, 'titi');
        $sample_list = $sample_data['items'];

        return json_encode($sample_list);
    }
}
