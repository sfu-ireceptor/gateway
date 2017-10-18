<?php

namespace App\Http\Controllers;

use App\RestService;

class HomeController extends Controller
{
    public function index()
    {
        // get metadata for form options
        $username = auth()->user()->username;
        $data = RestService::metadata2($username);

        $sample_data = RestService::samples(['ajax' => true], $username);
        $sample_list = $sample_data['items'];

        $data['sample_list_json'] = json_encode($sample_list);
        $data['brian'] = 'hello';

        return view('home', $data);
    }

    public function about()
    {
        return view('about');
    }
}
