<?php

namespace App\Http\Controllers;

use App\Bookmark;
use Illuminate\Http\Request;

class BookmarkController extends Controller
{
    public function getIndex()
    {
        $userId = auth()->user()->id;

        $bookmark_list = Bookmark::findGroupedByMonthForUser($userId);

        $data = [];
        $data['bookmark_list_grouped_by_month'] = $bookmark_list;

        $data['bookmark_list'] = Bookmark::where('user_id', '=', $userId)->orderBy('id', 'desc')->get();

        $data['notification'] = session()->get('notification');

        return view('bookmarkList', $data);
    }

    public function postAdd(Request $request)
    {
        $f = $request->all();

        $b = new Bookmark();
        $b->user_id = auth()->user()->id;
        $b->url = $f['url'];

        $b->save();

        return $b->id;
    }

    public function postDelete(Request $request)
    {
        $f = $request->all();

        $id = $f['id'];
        $userId = auth()->user()->id;

        $b = Bookmark::get($id, $userId);
        if ($b != null) {
            $b->delete();
        }
    }

    public function getDelete($id)
    {
        $userId = auth()->user()->id;

        $b = Bookmark::get($id, $userId);
        if ($b != null) {
            $b->delete();

            return redirect('bookmarks')->with('notification', 'The bookmark was successfully deleted.');
        }

        return redirect('bookmarks');
    }
}
