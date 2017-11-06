<?php

namespace App\Http\Controllers;

use App\RestService;
use Illuminate\Http\Request;

class SampleController extends Controller
{
    public function index(Request $request)
    {
        $username = auth()->user()->username;

        /*************************************************
        * prepare form widgets data
        *************************************************/

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

        // var_export($subject_gender_list);die();

        // view data
        $data = [];
        $data['subject_gender_list'] = $subject_gender_list;
        $data['subject_ethnicity_list'] = $subject_ethnicity_list;
        $data['cell_type_list'] = $cell_type_list;
        $data['sample_source_list'] = $sample_source_list;
        $data['dna_type_list'] = $dna_type_list;

        /*************************************************
        * get filtered sample list
        *************************************************/

        $sample_data = RestService::samples($request->all(), $username);

        // Go through the filtered samples and count the total number of sequences
        // returned by the query.
        $total_filtered_sequences = 0;
        foreach ($sample_data['items'] as $sample) {
            if (isset($sample->ir_sequence_count)) {
                $total_filtered_sequences = $total_filtered_sequences + $sample->ir_sequence_count;
            }
        }

        $data['sample_list'] = $sample_data['items'];
        $data['sample_list_json'] = json_encode($sample_data['items']);
        $data['rs_list'] = $sample_data['rs_list'];
        $data['total_filtered_samples'] = $sample_data['total'];
        $data['total_filtered_sequences'] = $total_filtered_sequences;

        // // Filters being used.
        // $data['filters'] = $sample_data['filters'];

        // Summary statistics of overall repositories
        $data['total_repositories'] = $metadata['total_repositories'];
        $data['total_labs'] = $metadata['total_labs'];
        $data['total_studies'] = $metadata['total_projects'];
        $data['total_samples'] = $metadata['total_samples'];
        $data['total_sequences'] = $metadata['total_sequences'];

        // re-populate form values
        $request->flash();

        return view('sample', $data);
    }

    // public function json(Request $request)
    // {
    //     $username = auth()->user()->username;

    //     // get sample list
    //     $params = $request->all();
    //     $params['ajax'] = true;

    //     $sample_data = RestService::samples($params, $username);
    //     $sample_list = $sample_data['items'];

    //     return json_encode($sample_list);
    // }

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
