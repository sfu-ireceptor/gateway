<?php

namespace App\Http\Controllers;

use App\Download;

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

       public function getCancel($id)
    {
        $d = Download::find($id);

		$username = auth()->user()->username;
	   if($d->username != $username) {
        	abort(403, 'Unauthorized action.');
        }

        if($d->status != Download::STATUS_QUEUED) {
        	abort(403, 'Unauthorized action.');
        }

        $d->setCanceled();
        $d->save();

        return redirect('downloads')->with('notification', 'The job was successfully cancelled.');
}
