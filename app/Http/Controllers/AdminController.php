<?php

namespace App\Http\Controllers;

use App\User;
use App\Agave;
use App\LocalJob;
use App\RestService;
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
        $jobs['agave'] = LocalJob::findLast('agave');

        $data = [];
        $data['jobs'] = $jobs;

        return view('queues', $data);
    }

    public function getDatabases()
    {
        $rs_list = RestService::all();

        $data = [];
        $data['rs_list'] = $rs_list;

        return view('databases', $data);
    }

    public function postUpdateDatabase(Request $request)
    {
        $id = $request->get('id');
        $enabled = filter_var($request->get('enabled'), FILTER_VALIDATE_BOOLEAN);

        $rs = RestService::find($id);
        $rs->enabled = $enabled;
        $rs->save();
    }

    public function getUsers()
    {
        // retrieve users from Agave
        $agave = new Agave;
        $token = auth()->user()->password;
        $l = $agave->getUsers($token);

        // sort by creation date desc
        usort($l, function ($a, $b) {
            return strcmp($b->create_time, $a->create_time);
        });

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

        $data = [];
        $data['notification'] = session()->get('notification');
        $data['l'] = $l;

        return view('userList', $data);
    }

    public function getAddUser()
    {
        $data = [];

        return view('userAdd', $data);
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
        $t = $agave->createUser($token, $firstName, $lastName, $email);

        // email credentials
        Mail::send(['text' => 'emails.auth.accountCreated'], $t, function ($message) use ($t) {
            $message->to($t['email'])->subject('iReceptor account');
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

        return view('userEdit', $data);
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
}
