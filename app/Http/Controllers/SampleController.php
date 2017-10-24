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
        $metadata = RestService::metadata2($username);

        // gender
        $subject_gender_list = [];
        $subject_gender_list[''] = '';
        foreach ($metadata['subject_gender'] as $v) {
            $subject_gender_list[$v] = $v;
        }

        // ethnicity
        $subject_ethnicity_list = [];
        $subject_ethnicity_list[''] = '';
        foreach ($metadata['subject_ethnicity'] as $v) {
            $subject_ethnicity_list[$v] = $v;
        }

        // cell type
        $cell_type_list = [];
        foreach ($metadata['ireceptor_cell_subset_name'] as $v) {
            $cell_type_list[$v] = $v;
        }

        // sample source
        $sample_source_list = [];
        foreach ($metadata['sample_source_name'] as $v) {
            $sample_source_list[$v] = $v;
        }

        // dna type
        $dna_type_list = [];
        foreach ($metadata['dna_type'] as $v) {
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

        // The rs_list is the response data from the service based on
        // the query parameters. It is this data that provides the details
        // about the results of the query.
        $nFilteredSamples = $sample_data['total'];
        $nFilteredSequences = 0;
        foreach ($sample_data['items'] as $sample) {
            $nFilteredSequences = $nFilteredSequences + $sample->sequence_count;
        }

        $data['sample_list'] = $sample_data['items'];
        $data['sample_list_json'] = json_encode($sample_data['items']);
        $data['rs_list'] = $sample_data['rs_list'];
        $data['total_samples'] = $sample_data['total'];

        // // Filters being used.
        // $data['filters'] = $sample_data['filters'];

        // // Summary statistics of overall repositories
        // $data['totalRepositories'] = $sample_data['totalRepositories'];
        // $data['totalLabs'] = $sample_data['totalLabs'];
        // $data['totalStudies'] = $sample_data['totalStudies'];
        // $data['totalSamples'] = $sample_data['totalSamples'];
        // $data['totalSequences'] = $sample_data['totalSequences'];

        // Summary statistics about the query with the filters applied.
        $data['nFilteredSamples'] = $nFilteredSamples;
        $data['nFilteredSequences'] = $nFilteredSequences;

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
