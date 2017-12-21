<?php

namespace App\Http\Controllers;

use App\RestService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        // get metadata for form options
        $username = auth()->user()->username;
        $data = RestService::metadata($username);

        $query_log_id = $request->get('query_log_id');
        $sample_data = RestService::samples(['ajax' => true], $username, $query_log_id);
        $sample_list = $sample_data['items'];

        $data['sample_list_json'] = json_encode($sample_list);
        $data['rest_service_list'] = $sample_data['rs_list'];

        return view('home', $data);
    }

    public function about()
    {
        return view('about');
    }
}
