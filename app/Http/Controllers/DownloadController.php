<?php

namespace App\Http\Controllers;

use App\Download;
use Illuminate\Http\Request;

class DownloadController extends Controller
{
    public function getIndex()
    {
        $username = auth()->user()->username;
        $download_list = Download::where('username', '=', $username)->orderBy('id', 'desc')->get();

        $data = [];
        $data['download_list'] = $download_list;

        // $data['notification'] = session()->get('notification');

        return view('downloadList', $data);
    }
}
