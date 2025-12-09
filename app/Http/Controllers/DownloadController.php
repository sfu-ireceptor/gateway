<?php

namespace App\Http\Controllers;

use App\Bookmark;
use App\Download;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class DownloadController extends Controller
{
    public function getIndex(Request $request)
    {
        $user = auth()->user();

        // if there are galaxy parameters, save them and redirect
        if ($request->has('GALAXY_URL') && $request->has('tool_id')) {
            $galaxy_url = $request->input('GALAXY_URL');
            $tool_id = $request->input('tool_id');

            $user->galaxy_url = $galaxy_url;
            $user->galaxy_tool_id = $tool_id;
            $user->save();

            return redirect('downloads')->with('notification', 'You can now send your downloads to Galaxy with the "Send to Galaxy" buttons.');
        }

        $username = $user->username;
        $download_list = Download::where('hidden', false)->where('username', '=', $username)->orderBy('id', 'desc')->get();

        $data = [];
        $data['download_list'] = $download_list;

        // if galaxy is enabled
        $data['galaxy_enabled'] = false;
        if (isset($user->galaxy_url)) {
            $data['galaxy_enabled'] = true;
            $data['galaxy_tool_id'] = $user->galaxy_tool_id;
            $data['galaxy_url'] = $user->galaxy_url;
        }

        return view('downloadList', $data);
    }

    public function getDownload($id)
    {
        $d = Download::find($id);

        $user = auth()->user();
        if (($d->username != $user->username) && (! $user->isAdmin())) {
            abort(403, 'Unauthorized action.');
        }

        // Code to limit downloads to other than "Standard" users.
        //if ($user->getStatus() == 'Standard') {
        //    return redirect('downloads')->with('notification', $user->getStatus() . ' users are not able to download data.');
        //}

        Log::debug('Download file = ' . $d->file_url);
        if (File::exists($d->file_url)) {
            return response()->download($d->file_url);
        }

        // If we get here the download did not work.
        return redirect('downloads')->with('notification', 'Unable to download data.');
    }

    public function getCancel($id)
    {
        $d = Download::find($id);

        $user = auth()->user();
        if (($d->username != $user->username) && (! $user->isAdmin())) {
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

        return redirect('downloads')->with('deleted_id', $id);
    }

    public function getUndoDelete($id)
    {
        $d = Download::find($id);

        $username = auth()->user()->username;
        if ($d->username != $username) {
            abort(403, 'Unauthorized action.');
        }

        $d->hidden = 0;
        $d->save();

        return redirect('downloads')->with('undo_deleted_id', $id);
    }

    public function getBookmark($id)
    {
        $d = Download::find($id);

        $username = auth()->user()->username;
        if ($d->username != $username) {
            abort(403, 'Unauthorized action.');
        }

        $b = new Bookmark;
        $b->user_id = auth()->user()->id;
        $b->url = $d->page_url;
        $b->save();

        return redirect('downloads')->with('bookmarked', $b->id);
    }
}
