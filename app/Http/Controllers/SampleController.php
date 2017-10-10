<?php

namespace App\Http\Controllers;

use App\RestService;
use Illuminate\Http\Request;

class SampleController extends Controller
{
    public function index(Request $request)
    {
        $username = auth()->user()->username;

        // get metadata for form options
        $metadata_data = RestService::metadata($username);
        // Build up global data (unfiltered).
        $repository_list = $metadata_data['rest_service_list'];
        $totalRepositories = count($repository_list);
        $totalLabs = 0;
        $totalProjects = 0;
        foreach ($repository_list as $repo) {
            foreach ($repo->labs as $lab) {
                $totalLabs = $totalLabs + 1;
                $totalProjects += count($lab->projects);
            }
        }

        // get filtered sample list
        $sample_data = RestService::samples($request->all(), $username);
        $filtered_samples = $sample_data['rs_list'];

        // The rs_list is the response data from the service based on
        // the query parameters. It is this data that provides the details
        // about the results of the query.
        $nFilteredSamples = $sample_data['total'];
        $nFilteredSequences = 0;
        foreach ($sample_data['items'] as $sample) {
            $nFilteredSequences = $nFilteredSequences + $sample->sequence_count;
        }

        $data = $metadata_data;
        $data['sample_list'] = $sample_data['items'];
        $data['sample_list_json'] = json_encode($sample_data['items']);
        $data['rs_list'] = $sample_data['rs_list'];
        $data['total_samples'] = $sample_data['total'];
        // Filters being used.
        $data['filters'] = $sample_data['filters'];
        // Summary statistics of overall repositories
        $data['totalRepositories'] = $sample_data['totalRepositories'];
        $data['totalLabs'] = $sample_data['totalLabs'];
        $data['totalStudies'] = $sample_data['totalStudies'];
        $data['totalSamples'] = $sample_data['totalSamples'];
        $data['totalSequences'] = $sample_data['totalSequences'];
        // Summary statistics about the query with the filters applied.
        $data['nFilteredSamples'] = $nFilteredSamples;
        $data['nFilteredSequences'] = $nFilteredSequences;

        // re-populate form values
        $request->flash();

        return view('sample', $data);
    }

    public function json(Request $request)
    {
        $username = auth()->user()->username;

        // get sample list
        $params = $request->all();
        $params['ajax'] = true;

        $sample_data = RestService::samples($params, $username);
        $sample_list = $sample_data['items'];

        return json_encode($sample_list);
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
