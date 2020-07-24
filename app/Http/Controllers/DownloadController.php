<?php

namespace App\Http\Controllers;

use App\Download;
use Illuminate\Support\Facades\Log;

class DownloadController extends Controller
{
    public function getIndex()
    {
        $username = auth()->user()->username;
        $download_list = Download::where('hidden', false)->where('username', '=', $username)->orderBy('id', 'desc')->get();

        $data = [];
        $data['download_list'] = $download_list;

        return view('downloadList', $data);
    }

    public function getCancel($id)
    {
        $d = Download::find($id);

        $username = auth()->user()->username;
        if ($d->username != $username) {
            abort(403, 'Unauthorized action.');
        }

        if ($d->status != Download::STATUS_QUEUED) {
            abort(403, 'Unauthorized action.');
        }

        $d->setCanceled();
        $d->save();

        return redirect('downloads')->with('notification', 'The job was successfully cancelled.');
    }

    public function getDelete($id)
    {
        $d = Download::find($id);

        $username = auth()->user()->username;
        if ($d->username != $username) {
            abort(403, 'Unauthorized action.');
        }
        
        $d->hidden = 1;
        $d->save();

        return redirect('downloads')->with('notification', 'Your download has been deleted.');
    }
}
