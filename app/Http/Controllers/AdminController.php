<?php

namespace App\Http\Controllers;

use App\CachedSample;
use App\Download;
use App\FieldName;
use App\Job;
use App\Jobs\CountCells;
use App\Jobs\CountClones;
use App\Jobs\CountEpitopes;
use App\Jobs\CountSequences;
use App\LocalJob;
use App\News;
use App\QueryLog;
use App\RestService;
use App\Sample;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    public function getQueues()
    {
        // Check to see if user is Admin, if not return unautorized message.
        $user = User::where('username', auth()->user()->username)->first();
        if ($user == null || ! $user->isAdmin()) {
            abort(401, 'Not authorized.');
        }

        $jobs = [];
        $jobs['admin'] = LocalJob::findLast('admin');
        $jobs['short-downloads'] = LocalJob::findLast('short-downloads');
        $jobs['long-downloads'] = LocalJob::findLast('long-downloads');
        $jobs['short-analysis-jobs'] = LocalJob::findLast('short-analysis-jobs');
        $jobs['long-analysis-jobs'] = LocalJob::findLast('long-analysis-jobs');
        $jobs['agave-notifications'] = LocalJob::findLast('agave-notifications');

        $data = [];
        $data['jobs'] = $jobs;

        return view('queues', $data);
    }

    public function getDatabases()
    {
        // Check to see if user is Admin, if not return unautorized message.
        $user = User::where('username', auth()->user()->username)->first();
        if ($user == null || ! $user->isAdmin()) {
            abort(401, 'Not authorized.');
        }

        $rs_list = RestService::findAvailable();

        $data = [];
        $data['rs_list'] = $rs_list;
        $data['notification'] = session()->get('notification');

        return view('databases', $data);
    }

    public function getUpdateDatabase($id, $enabled)
    {
        // Check to see if user is Admin, if not return unautorized message.
        $user = User::where('username', auth()->user()->username)->first();
        if ($user == null || ! $user->isAdmin()) {
            abort(401, 'Not authorized.');
        }

        $enabled = filter_var($enabled, FILTER_VALIDATE_BOOLEAN);

        $rs = RestService::find($id);
        $rs->enabled = $enabled;
        $rs->save();

        $message = $rs->name . ' was successfully ';
        $message .= $enabled ? 'enabled' : 'disabled';

        return redirect('admin/databases')->with('notification', $message);
    }

    public function getDatabaseStats($id)
    {
        // Check to see if user is Admin, if not return unautorized message.
        $user = User::where('username', auth()->user()->username)->first();
        if ($user == null || ! $user->isAdmin()) {
            abort(401, 'Not authorized.');
        }

        $response_list = RestService::samples([], 'titi', true, [$id]);

        $sample_list = [];
        $rs = [];
        foreach ($response_list as $i => $response) {
            $rs = $response['rs'];
            $sample_list = $response['data'];
            $sample_list = Sample::convert_sample_list($sample_list, $rs);
        }

        $data['sample_list'] = $sample_list;
        $data['rs'] = $rs;

        return view('databasesStats', $data);
    }

    public function getNews()
    {
        // Check to see if user is Admin, if not return unautorized message.
        $user = User::where('username', auth()->user()->username)->first();
        if ($user == null || ! $user->isAdmin()) {
            abort(401, 'Not authorized.');
        }

        $data = [];
        $data['news_list'] = News::orderBy('created_at', 'desc')->get();
        $data['notification'] = session()->get('notification');

        return view('news/list', $data);
    }

    public function getAddNews()
    {
        // Check to see if user is Admin, if not return unautorized message.
        $user = User::where('username', auth()->user()->username)->first();
        if ($user == null || ! $user->isAdmin()) {
            abort(401, 'Not authorized.');
        }

        $data = [];

        return view('news/add', $data);
    }

    public function postAddNews(Request $request)
    {
        // Check to see if user is Admin, if not return unautorized message.
        $user = User::where('username', auth()->user()->username)->first();
        if ($user == null || ! $user->isAdmin()) {
            abort(401, 'Not authorized.');
        }

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
        // Check to see if user is Admin, if not return unautorized message.
        $user = User::where('username', auth()->user()->username)->first();
        if ($user == null || ! $user->isAdmin()) {
            abort(401, 'Not authorized.');
        }

        $news = News::find($id);

        $data = [];
        $data['n'] = $news;

        return view('news/edit', $data);
    }

    public function postEditNews(Request $request)
    {
        // Check to see if user is Admin, if not return unautorized message.
        $user = User::where('username', auth()->user()->username)->first();
        if ($user == null || ! $user->isAdmin()) {
            abort(401, 'Not authorized.');
        }

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
        // Check to see if user is Admin, if not return unautorized message.
        $user = User::where('username', auth()->user()->username)->first();
        if ($user == null || ! $user->isAdmin()) {
            abort(401, 'Not authorized.');
        }

        $n = News::find($id);
        $n->delete();

        return redirect('admin/news')->with('notification', 'News was successfully deleted.');
    }

    public function getUsers($sort = 'created_at')
    {
        // Check to see if user is Admin, if not return unautorized message.
        $user = User::where('username', auth()->user()->username)->first();
        if ($user == null || ! $user->isAdmin()) {
            abort(401, 'Not authorized.');
        }

        $l = User::orderByDesc('created_at')->get();

        $data = [];
        $data['notification'] = session()->get('notification');
        $data['l'] = $l;

        return view('user/list', $data);
    }

    public function getUsers2($sort = 'created_at')
    {
        // Check to see if user is Admin, if not return unautorized message.
        $user = User::where('username', auth()->user()->username)->first();
        if ($user == null || ! $user->isAdmin()) {
            abort(401, 'Not authorized.');
        }

        $l = User::orderByDesc('created_at')->get();

        $data = [];
        $data['l'] = $l;

        return view('user/list2', $data);
    }

    public function getAddUser()
    {
        // Check to see if user is Admin, if not return unautorized message.
        $user = User::where('username', auth()->user()->username)->first();
        if ($user == null || ! $user->isAdmin()) {
            abort(401, 'Not authorized.');
        }

        $data = [];

        return view('user/add', $data);
    }

    public function postAddUser(Request $request)
    {
        // Check to see if user is Admin, if not return unautorized message.
        $user = User::where('username', auth()->user()->username)->first();
        if ($user == null || ! $user->isAdmin()) {
            abort(401, 'Not authorized.');
        }

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
        $password = str_random(24);

        $u = User::add($firstName, $lastName, $email, $password);

        $t = [];
        $t['login_link'] = config('app.url') . '/login';
        $t['first_name'] = $u->first_name;
        $t['username'] = $u->username;
        $t['password'] = $password;

        // email credentials
        try {
            Mail::send(['text' => 'emails.auth.accountCreated'], $t, function ($message) use ($u) {
                $message->to($u->email)->subject('iReceptor account');
            });
        } catch (\Exception $e) {
            Log::error('AdminController::postAddUser - Add user email delivery failed');
            Log::error('AdminController::postAddUser - ' . $e->getMessage());
        }

        return redirect('admin/users')->with('notification', 'User ' . $u->username . ' has been created. An email with credentials was sent.');
    }

    public function getEditUser($id)
    {
        // Check to see if user is Admin, if not return unautorized message.
        $user = User::where('username', auth()->user()->username)->first();
        if ($user == null || ! $user->isAdmin()) {
            abort(401, 'Not authorized.');
        }

        $user = User::find($id);

        $data = [];
        $data['id'] = $id;
        $data['first_name'] = $user->first_name;
        $data['last_name'] = $user->last_name;
        $data['email'] = $user->email;
        $data['country'] = $user->country;
        $data['institution'] = $user->institution;

        return view('user/edit', $data);
    }

    public function postEditUser(Request $request)
    {
        // Check to see if user is Admin, if not return unautorized message.
        $user = User::where('username', auth()->user()->username)->first();
        if ($user == null || ! $user->isAdmin()) {
            abort(401, 'Not authorized.');
        }

        // validate form
        $rules = [
            'id' => 'required',
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
            $id = $request->get('id');

            return redirect('admin/edit-user/' . $id)->withErrors($validator);
        }

        $user = User::find($request->get('id'));
        $user->first_name = $request->get('first_name');
        $user->last_name = $request->get('last_name');
        $user->email = $request->get('email');
        $user->country = $request->get('country');
        $user->institution = $request->get('institution');
        $user->save();

        return redirect('admin/users')->with('notification', 'Modifications for ' . $user->first_name . ' ' . $user->lastName . ' were successfully saved.');
    }

    public function getUpdateSampleCache()
    {
        // Check to see if user is Admin, if not return unautorized message.
        $user = User::where('username', auth()->user()->username)->first();
        if ($user == null || ! $user->isAdmin()) {
            abort(401, 'Not authorized.');
        }

        $n = CachedSample::cache();

        $message = "$n samples have been retrieved and cached.";

        // delete cached sata
        Cache::flush();

        return redirect('admin/databases')->with('notification', $message);
    }

    public function getUpdateSequenceCount($rest_service_id)
    {
        // Check to see if user is Admin, if not return unautorized message.
        $user = User::where('username', auth()->user()->username)->first();
        if ($user == null || ! $user->isAdmin()) {
            abort(401, 'Not authorized.');
        }

        $rs = RestService::find($rest_service_id);
        $username = auth()->user()->username;

        $queue = 'admin';
        $lj = new LocalJob($queue);
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

    public function getUpdateCloneCount($rest_service_id)
    {
        // Check to see if user is Admin, if not return unautorized message.
        $user = User::where('username', auth()->user()->username)->first();
        if ($user == null || ! $user->isAdmin()) {
            abort(401, 'Not authorized.');
        }

        $rs = RestService::find($rest_service_id);
        $username = auth()->user()->username;

        $queue = 'admin';
        $lj = new LocalJob($queue);
        $lj->user = $username;
        $lj->queue = 'admin';
        $lj->description = 'Clone count for  ' . $rs->name;
        $lj->save();

        // queue as a job
        $localJobId = $lj->id;
        CountClones::dispatch($username, $rest_service_id, $localJobId)->onQueue('admin');

        $message = 'Clone count job for  ' . $rs->name . ' has been <a href="/admin/queues">queued</a>';

        return redirect('admin/databases')->with('notification', $message);
    }

    public function getUpdateCellCount($rest_service_id)
    {
        // Check to see if user is Admin, if not return unautorized message.
        $user = User::where('username', auth()->user()->username)->first();
        if ($user == null || ! $user->isAdmin()) {
            abort(401, 'Not authorized.');
        }

        $rs = RestService::find($rest_service_id);
        $username = auth()->user()->username;

        $queue = 'admin';
        $lj = new LocalJob($queue);
        $lj->user = $username;
        $lj->queue = 'admin';
        $lj->description = 'Cell count for  ' . $rs->name;
        $lj->save();

        // queue as a job
        $localJobId = $lj->id;
        CountCells::dispatch($username, $rest_service_id, $localJobId)->onQueue('admin');

        $message = 'Cell count job for  ' . $rs->name . ' has been <a href="/admin/queues">queued</a>';

        return redirect('admin/databases')->with('notification', $message);
    }

    public function getUpdateEpitopes($rest_service_id)
    {
        // Check to see if user is Admin, if not return unautorized message.
        $user = User::where('username', auth()->user()->username)->first();
        if ($user == null || ! $user->isAdmin()) {
            abort(401, 'Not authorized.');
        }

        $rs = RestService::find($rest_service_id);
        $username = auth()->user()->username;

        $queue = 'admin';
        $lj = new LocalJob($queue);
        $lj->user = $username;
        $lj->queue = 'admin';
        $lj->description = 'Epitope count for  ' . $rs->name;
        $lj->save();

        // queue as a job
        $localJobId = $lj->id;
        CountEpitopes::dispatch($username, $rest_service_id, $localJobId)->onQueue('admin');

        $message = 'Epitope count job for  ' . $rs->name . ' has been <a href="/admin/queues">queued</a>';

        return redirect('admin/databases')->with('notification', $message);
    }

    public function getUpdateChunkSize($id)
    {
        // Check to see if user is Admin, if not return unautorized message.
        $user = User::where('username', auth()->user()->username)->first();
        if ($user == null || ! $user->isAdmin()) {
            abort(401, 'Not authorized.');
        }

        $rs = RestService::find($id);

        $info = $rs->refreshInfo();

        if (isset($info['error'])) {
            $message = 'An error occurred : ' . $info['error'];
        } else {
            $chunk_size = $info['chunk_size'] ?? null;
            if ($chunk_size == null) {
                $message = $rs->name . ' doesn\'t have a max_size. ';
            } elseif (is_string($chunk_size)) {
                $message = 'An error occurred when trying to retrieve max_size from ' . $rs->name . ': ' . $chunk_size . '. ';
            } else {
                $message = $rs->name . ' max_size was successfully updated to ' . $chunk_size . '. ';
            }

            $api_version = $info['api_version'] ?? '1.0';
            if ($api_version == null) {
                $message .= $rs->name . ' did not specify an API version. ';
            } else {
                $message .= 'API version is ' . $api_version . '. ';
            }

            $stats = $rs->refreshStatsCapability();
            if ($stats) {
                $message .= 'Stats are available. ';
            } else {
                $message .= 'Stats are not available. ';
            }
        }

        return redirect('admin/databases')->with('notification', $message);
    }

    public function getFieldNames($api_version = null)
    {
        // Check to see if user is Admin, if not return unautorized message.
        $user = User::where('username', auth()->user()->username)->first();
        if ($user == null || ! $user->isAdmin()) {
            abort(401, 'Not authorized.');
        }

        $api_version = $api_version ?? config('ireceptor.default_api_version');

        $api_version_list = FieldName::getAPIVersions();

        $field_name_list = FieldName::where('api_version', $api_version)->get()->toArray();

        $data = [];
        $data['api_version'] = $api_version;
        $data['api_version_list'] = $api_version_list;
        $data['field_name_list'] = $field_name_list;

        return view('fieldNames', $data);
    }

    public function queries($nb_months = null)
    {
        // Check to see if user is Admin, if not return unautorized message.
        $user = User::where('username', auth()->user()->username)->first();
        if ($user == null || ! $user->isAdmin()) {
            abort(401, 'Not authorized.');
        }

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
        // Check to see if user is Admin, if not return unautorized message.
        $user = User::where('username', auth()->user()->username)->first();
        if ($user == null || ! $user->isAdmin()) {
            abort(401, 'Not authorized.');
        }

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
        // Check to see if user is Admin, if not return unautorized message.
        $user = User::where('username', auth()->user()->username)->first();
        if ($user == null || ! $user->isAdmin()) {
            abort(401, 'Not authorized.');
        }

        return $this->queries2($n);
    }

    public function queriesMonths($n)
    {
        // Check to see if user is Admin, if not return unautorized message.
        $user = User::where('username', auth()->user()->username)->first();
        if ($user == null || ! $user->isAdmin()) {
            abort(401, 'Not authorized.');
        }

        return $this->queries($n);
    }

    public function query($id)
    {
        // Check to see if user is Admin, if not return unautorized message.
        $user = User::where('username', auth()->user()->username)->first();
        if ($user == null || ! $user->isAdmin()) {
            abort(401, 'Not authorized.');
        }

        $data = [];
        $data['query_id'] = $id;
        $data['q'] = QueryLog::find($id);
        $data['node_queries'] = QueryLog::find_node_queries($id);

        return view('query', $data);
    }

    public function downloads()
    {
        // Check to see if user is Admin, if not return unautorized message.
        $user = User::where('username', auth()->user()->username)->first();
        if ($user == null || ! $user->isAdmin()) {
            abort(401, 'Not authorized.');
        }

        $download_list = Download::orderBy('id', 'desc')->get();

        $data = [];
        $data['download_list'] = $download_list;

        return view('allDownloads', $data);
    }

    public function jobs()
    {
        // Check to see if user is Admin, if not return unautorized message.
        $user = User::where('username', auth()->user()->username)->first();
        if ($user == null || ! $user->isAdmin()) {
            abort(401, 'Not authorized.');
        }

        // Get the list of jobs
        $job_list = Job::orderBy('updated_at', 'desc')->get();
        // The job info doesn't have username, just user ID. Add the
        // username to the info provided to the blade for the UI.
        $new_list = [];
        foreach ($job_list as $job) {
            $new_job = $job;
            $new_job['username'] = User::where('id', $job->user_id)->first()->username;
            $new_list[] = $new_job;
        }

        // Set up the data and pass it to the view.
        $data = [];
        $data['job_list'] = $new_list;

        return view('allJobs', $data);
    }

    public function downloadsMultipleIPAs()
    {
        // Check to see if user is Admin, if not return unautorized message.
        $user = User::where('username', auth()->user()->username)->first();
        if ($user == null || ! $user->isAdmin()) {
            abort(401, 'Not authorized.');
        }

        $download_list = Download::orderBy('id', 'desc')->get();

        $download_list_filtered = [];
        foreach ($download_list as $d) {
            $node_queries = null;
            // time_nanosleep(0, 10000000);

            Log::debug('Parsing download ' . $d['id'] . ' from ' . $d['start_date'] . ', memory=' . human_filesize(memory_get_usage()));

            $query_log_id = $d->query_log_id;
            $q = QueryLog::find($query_log_id);

            QueryLog::where('parent_id', '=', $query_log_id)->where('rest_service_name', 'like', 'IPA%')->where('result_size', '>', 0)->chunk(10, function ($node_queries) use ($d, &$download_list_filtered) {
                $nb_ipa_queries = 0;
                foreach ($node_queries as $nq) {
                    // dd($nq);
                    if (isset($nq['params']) && is_string($nq['params']) && str_contains($nq['params'], 'tsv')) {
                        $nb_ipa_queries++;
                        if ($nb_ipa_queries >= 2) {
                            $download_list_filtered[] = $d;
                            break;
                        }
                    }
                }
            });

            // $node_queries = QueryLog::find_node_queries($query_log_id);

            // dd($node_queries);

            // $nb_ipa_queries = 0;
            // foreach ($node_queries as $nq) {
            //     if (isset($nq['params']) && is_string($nq['params']) && str_contains($nq['params'], 'tsv')) {
            //         if (isset($nq['rest_service_name']) && str_contains($nq['rest_service_name'], 'IPA')) {
            //             if (isset($nq['result_size']) && $nq['result_size'] > 0) {
            //                 $nb_ipa_queries++;
            //                 if ($nb_ipa_queries >= 2) {
            //                     $download_list_filtered[] = $d;
            //                     break;
            //                 }
            //             }
            //         }
            //     }
            // }

            // unset($q);
            // unset($node_queries);
        }

        // dd($download_list_filtered);

        $data = [];
        $data['download_list'] = $download_list_filtered;

        return view('allDownloads', $data);
    }
}
