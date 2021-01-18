<?php

namespace App\Http\Controllers;

use App\Agave;
use App\CachedSample;
use App\Download;
use App\FieldName;
use App\Jobs\CountSequences;
use App\LocalJob;
use App\News;
use App\QueryLog;
use App\RestService;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    public function getQueues()
    {
        $jobs = [];
        $jobs['default'] = LocalJob::findLast('default');
        $jobs['long'] = LocalJob::findLast('long');
        $jobs['agave'] = LocalJob::findLast('agave');
        $jobs['admin'] = LocalJob::findLast('admin');

        $data = [];
        $data['jobs'] = $jobs;

        return view('queues', $data);
    }

    public function getDatabases()
    {
        $rs_list = RestService::findAvailable();

        $data = [];
        $data['rs_list'] = $rs_list;
        $data['notification'] = session()->get('notification');

        return view('databases', $data);
    }

    public function getUpdateDatabase($id, $enabled)
    {
        $enabled = filter_var($enabled, FILTER_VALIDATE_BOOLEAN);

        $rs = RestService::find($id);
        $rs->enabled = $enabled;
        $rs->save();

        $message = $rs->name . ' was successfully ';
        $message .= $enabled ? 'enabled' : 'disabled';

        return redirect('admin/databases')->with('notification', $message);
    }

    public function getNews()
    {
        $data = [];
        $data['news_list'] = News::orderBy('created_at', 'desc')->get();
        $data['notification'] = session()->get('notification');

        return view('news/list', $data);
    }

    public function getAddNews()
    {
        $data = [];

        return view('news/add', $data);
    }

    public function postAddNews(Request $request)
    {
        // validate form
        $rules = [
            'message' => 'required',
        ];

        $messages = [
            'required' => 'This field is required.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            $request->flash();

            return redirect('admin/add-news')->withErrors($validator);
        }

        $message = $request->get('message');

        $n = new News;
        $n->message = $message;

        $n->save();

        return redirect('admin/news')->with('notification', 'The news has been successfully created.');
    }

    public function getEditNews($id)
    {
        $news = News::find($id);

        $data = [];
        $data['n'] = $news;

        return view('news/edit', $data);
    }

    public function postEditNews(Request $request)
    {
        // validate form
        $rules = [
            'message' => 'required',
        ];

        $messages = [
            'required' => 'This field is required.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            $request->flash();
            $username = $request->get('id');

            return redirect('admin/edit-news/' . $id)->withErrors($validator);
        }

        $id = $request->get('id');
        $message = $request->get('message');

        $n = News::find($id);
        $n->message = $message;
        $n->save();

        return redirect('admin/news')->with('notification', 'Modifications were successfully saved.');
    }

    public function getDeleteNews($id)
    {
        $n = News::find($id);
        $n->delete();

        return redirect('admin/users')->with('notification', 'News was successfully deleted.');
    }

    public function getUsers($sort = 'create_time')
    {
        // retrieve users from Agave
        $agave = new Agave;
        $token = auth()->user()->password;
        $l = $agave->getUsers($token);

        // fetch complementary user information from our local database
        $db_users = [];
        foreach (User::all() as $user) {
            $db_users[$user->username] = $user;
        }

        // add complementary user information to user list
        foreach ($l as $u) {
            $u->updated_at = '';
            $u->admin = false;

            if (isset($db_users[$u->username])) {
                $db_user = $db_users[$u->username];
                $u->updated_at = $db_user->updated_at;
                $u->admin = $db_user->admin;
            }
        }

        // sort by creation date desc
        usort($l, function ($a, $b) use ($sort) {
            return strcmp($b->{$sort}, $a->{$sort});
        });

        $data = [];
        $data['notification'] = session()->get('notification');
        $data['l'] = $l;

        return view('user/list', $data);
    }

    public function getAddUser()
    {
        $data = [];

        return view('user/add', $data);
    }

    public function postAddUser(Request $request)
    {
        // validate form
        $rules = [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email|unique:user,username',
        ];

        $messages = [
            'required' => 'This field is required.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            $request->flash();

            return redirect('admin/add-user')->withErrors($validator);
        }

        $firstName = $request->get('first_name');
        $lastName = $request->get('last_name');
        $email = $request->get('email');

        // create Agave account
        $agave = new Agave;
        $token = $agave->getAdminToken();
        $username = $agave->generateUsername($firstName, $lastName, $token);
        $u = $agave->createUser($token, $username, $firstName, $lastName, $email);

        $t = [];
        $t['login_link'] = config('app.url') . '/login';
        $t['first_name'] = $u['first_name'];
        $t['username'] = $u['username'];
        $t['password'] = $u['password'];

        // email credentials
        Mail::send(['text' => 'emails.auth.accountCreated'], $t, function ($message) use ($u) {
            $message->to($u['email'])->subject('iReceptor account');
        });

        Log::info('An account has been created for user ' . $t['username'] . '. Pwd: ' . $t['password']);

        return redirect('admin/users')->with('notification', 'The user ' . $t['username'] . ' has been created. An email with credentials was sent. Remember to add the user to the iReceptor services.');
    }

    public function getEditUser($username)
    {
        $agave = new Agave;
        $token = auth()->user()->password;

        $l = $agave->getUser($username, $token);
        $l = $l->result;

        $data = [];
        $data['username'] = $l->username;
        $data['first_name'] = $l->first_name;
        $data['last_name'] = $l->last_name;
        $data['email'] = $l->email;

        return view('user/edit', $data);
    }

    public function postEditUser(Request $request)
    {
        // validate form
        $rules = [
            'username' => 'required',
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email|unique:user,username',
        ];

        $messages = [
            'required' => 'This field is required.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            $request->flash();
            $username = $request->get('username');

            return redirect('admin/edit-user/' . $username)->withErrors($validator);
        }

        $username = $request->get('username');
        $firstName = $request->get('first_name');
        $lastName = $request->get('last_name');
        $email = $request->get('email');

        // create Agave account
        $agave = new Agave;
        $token = $agave->getAdminToken();
        $t = $agave->updateUser($token, $username, $firstName, $lastName, $email);

        return redirect('admin/users')->with('notification', 'Modifications for user ' . $username . ' were successfully saved.');
    }

    public function getDeleteUser($username)
    {
        // create Agave account
        $agave = new Agave;
        $token = $agave->getAdminToken();
        $agave->deleteUser($token, $username);

        return redirect('admin/users')->with('notification', 'User ' . $username . ' was successfully deleted.');
    }

    public function getUpdateSampleCache()
    {
        $username = auth()->user()->username;
        $n = CachedSample::cache();

        $message = "$n samples have been retrieved and cached.";

        return redirect('admin/databases')->with('notification', $message);
    }

    public function getUpdateSequenceCount($rest_service_id)
    {
        $rs = RestService::find($rest_service_id);
        $username = auth()->user()->username;

        $lj = new LocalJob();
        $lj->user = $username;
        $lj->queue = 'admin';
        $lj->description = 'Sequence count for  ' . $rs->name;
        $lj->save();

        // queue as a job
        $localJobId = $lj->id;
        CountSequences::dispatch($username, $rest_service_id, $localJobId)->onQueue('admin');

        $message = 'Sequence count job for  ' . $rs->name . ' has been <a href="/admin/queues">queued</a>';

        return redirect('admin/databases')->with('notification', $message);
    }

    public function getUpdateChunkSize($id)
    {
        $rs = RestService::find($id);
        $chunk_size = $rs->refreshChunkSize();

        if ($chunk_size == null) {
            $message = $rs->name . ' doesn\'t have a max_size';
        } elseif (is_string($chunk_size)) {
            $message = 'An error occured when trying to retrieve max_size from ' . $rs->name . ': ' . $chunk_size;
        } else {
            $message = $rs->name . ' max_size was successfully updated to ' . $chunk_size;
        }

        $stats = $rs->refreshStatsCapability();
        if($stats) {
            $message .= '. Stats are available.';
        }
        else {
            $message .= '. Stats are not available.';
        }

        return redirect('admin/databases')->with('notification', $message);
    }

    public function getFieldNames()
    {
        $data = [];
        $data['field_name_list'] = FieldName::all()->toArray();

        return view('fieldNames', $data);
    }

    public function queries($nb_months = null)
    {
        $data = [];
        $data['nb_months'] = $nb_months;
        $data['queries'] = QueryLog::find_gateway_queries($nb_months);

        $data['service_request_timeout'] = config('ireceptor.service_request_timeout');
        $data['gateway_request_timeout'] = config('ireceptor.gateway_request_timeout');
        $data['service_file_request_timeout'] = config('ireceptor.service_file_request_timeout');
        $data['gateway_file_request_timeout'] = config('ireceptor.gateway_file_request_timeout');
        $data['service_request_timeout_samples'] = config('ireceptor.service_request_timeout_samples');

        return view('queries', $data);
    }

    public function queries2($nb_months = null)
    {
        $data = [];
        $data['nb_months'] = $nb_months;
        $query_list = QueryLog::find_gateway_queries($nb_months);

        $l = [];
        foreach ($query_list as $q) {
            if ($q->username != 'titi' && $q->username != 'bcorrie' && $q->username != 'frances_breden' && $q->username != 'bojanz' && $q->username != 'scott_christley') {
                $l[] = $q;
            }
        }
        $data['queries'] = $l;

        return view('queries2', $data);
    }

    public function queriesMonths2($n)
    {
        return $this->queries2($n);
    }

    public function queriesMonths($n)
    {
        return $this->queries($n);
    }

    public function query($id)
    {
        $data = [];
        $data['query_id'] = $id;
        $data['q'] = QueryLog::find($id);
        $data['node_queries'] = QueryLog::find_node_queries($id);

        return view('query', $data);
    }

    public function downloads()
    {
        $download_list = Download::orderBy('id', 'desc')->get();

        $data = [];
        $data['download_list'] = $download_list;

        return view('allDownloads', $data);
    }
}
