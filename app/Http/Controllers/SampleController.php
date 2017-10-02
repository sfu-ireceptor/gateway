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
        $data['sample_list_json'] = json_encode($sample_data['items']);
        $data['rs_list'] = $sample_data['rs_list'];
        $data['total_samples'] = $sample_data['total'];

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
