<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;

class HomeController extends Controller
{
    public function index()
    {
       return view('home');
    }

    public function about()
    {
       return view('about');
    }
}
