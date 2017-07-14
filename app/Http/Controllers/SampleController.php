<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

use App\User;
use App\Agave;
use App\RestService;

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