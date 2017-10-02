<?php

namespace App\Http\Controllers;

use App\RestService;

class HomeController extends Controller
{
    public function index()
    {
        $username = auth()->user()->username;
        $sample_data = RestService::samples(['ajax' => true], $username);
        $sample_list = $sample_data['items'];

        $data = [];
        $data['sample_list_json'] = json_encode($sample_list);

        return view('home', $data);
    }

    public function about()
    {
        return view('about');
    }
}
