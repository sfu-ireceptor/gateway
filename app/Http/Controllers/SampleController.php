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
        $data = RestService::metadata($username);

        // get sample list
        $sample_data = RestService::samples($request->all(), $username);
        $data['sample_list'] = $sample_data['items'];
        $data['rs_list'] = $sample_data['rs_list'];
        $data['total_samples'] = $sample_data['total'];

        // re-populate form values
        $request->flash();

        return view('sample', $data);
    }
}
