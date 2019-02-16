<?php

namespace App\Http\Controllers;

use App\Sample;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        // get count of available data (sequences, samples)
        $username = auth()->user()->username;
        $metadata = Sample::metadata($username);
        $data = $metadata;

        // get list of samples
        $sample_list = Sample::public_samples();
        $data['sample_list_json'] = json_encode($sample_list);

        // generate statistics
        $sample_data = Sample::stats($sample_list);
        $data['rest_service_list'] = $sample_data['rs_list'];

        // cell type
        $cell_type_list = [];
        foreach ($metadata['cell_subset'] as $v) {
            $cell_type_list[$v] = $v;
        }
        $data['cell_type_list'] = $cell_type_list;

        // organism
        $subject_organism_list = [];
        $subject_organism_list[''] = 'Any';
        foreach ($metadata['organism'] as $v) {
            $subject_organism_list[$v] = $v;
        }
        $data['subject_organism_list'] = $subject_organism_list;

        // clear any lingering form data
        $request->session()->forget('_old_input');

        return view('home', $data);
    }

    public function about()
    {
        return view('about');
    }
}
