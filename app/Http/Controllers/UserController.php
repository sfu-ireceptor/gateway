<?php

namespace App\Http\Controllers;

use App\User;
use App\Agave;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function getLogin()
    {
        return view('user/login');
    }

    public function postLogin(Request $request)
    {
        $username = $request->input('username');
        $password = $request->input('password');

        // try to get Agave OAuth token
        $agave = new Agave;
        $t = $agave->getTokenForUser($username, $password);

        // if fail -> display form with error
        if ($t == null) {
            return redirect()->back()->withErrors(['Invalid credentials']);
        }

        // create user in local DB if necessary
        $user = User::where('username', $username)->first();
        if ($user == null) {
            // get user info from Agave
            $token = $agave->getAdminToken();
            $u = $agave->getUser($username, $token);
            $u = $u->result;

            // create user
            $user = new User();

            $user->username = $username;
            $user->first_name = $u->first_name;
            $user->last_name = $u->last_name;
            $user->email = $u->email;

            $user->save();
        }

        // save Agave OAuth token in local DB
        $user->updateToken($t);

        // log user in
        auth()->login($user);

        return redirect()->intended('home');
    }

    public function getLogout()
    {
        auth()->logout();

        return redirect('user/login');
    }

    public function getChangePassword()
    {
        $data = [];
        $data['notification'] = session('notification');

        return view('user/changePassword', $data);
    }

    public function postChangePassword(Request $request)
    {
        // custom form validation rule to check user's current password
        Validator::extend('current_password', function ($field, $value, $parameters) {
            $username = auth()->user()->username;
            $password = $value;

            $agave = new Agave;
            $t = $agave->getTokenForUser($username, $password);

            return $t != null;
        });

        // validate form
        $rules = [
            'current_password' => 'required|current_password',
            'password' => 'required|min:6',
            'password_confirmation' => 'required|same:password',
        ];

        $messages = [
            'required' => 'Required.',
            'min' => 'Must have at least :min characters.',
            'same' => 'Didn\'t match',
            'current_password' => 'Invalid',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            $request->flash();

            return redirect('user/change-password')->withErrors($validator);
        }

        $username = auth()->user()->username;

        $agave = new Agave;
        $token = $agave->getAdminToken();
        $l = $agave->getUser($username, $token);
        $user = $l->result;

        $first_name = $user->first_name;
        $last_name = $user->last_name;
        $email = $user->email;

        $password = $request->input('password');

        $t = $agave->updateUser($token, $username, $first_name, $last_name, $email, $password);

        return redirect('user/account')->with('notification', 'Your password was successfully changed.');
    }

    public function getAccount()
    {
        $username = auth()->user()->username;

        $agave = new Agave;
        $token = $agave->getAdminToken();
        $l = $agave->getUser($username, $token);
        $user = $l->result;

        $data = [];
        $data['user'] = $user;
        $data['notification'] = session('notification');

        return view('user/account', $data);
    }

    public function getChangePersonalInfo()
    {
        $agave = new Agave;
        $token = $agave->getAdminToken();

        $username = auth()->user()->username;
        $l = $agave->getUser($username, $token);
        $l = $l->result;

        $data = [];
        $data['username'] = $l->username;
        $data['first_name'] = $l->first_name;
        $data['last_name'] = $l->last_name;
        $data['email'] = $l->email;
        $data['notification'] = session('notification');

        return view('user/changePersonalInfo', $data);
    }

    public function postChangePersonalInfo(Request $request)
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

            return redirect('/user/change-personal-info')->withErrors($validator);
        }

        $username = auth()->user()->username;
        $firstName = $request->input('first_name');
        $lastName = $request->input('last_name');
        $email = $request->input('email');

        $agave = new Agave;
        $token = $agave->getAdminToken();
        $t = $agave->updateUser($token, $username, $firstName, $lastName, $email);

        return redirect('/user/account')->with('notification', 'Personal information was successfully chaged.');
    }

    public function getForgotPassword()
    {
        return view('user/forgotPassword');
        // return view('auth/passwords/reset');
    }

    public function postForgotPassword(Request $request)
    {
        // validate form
        $rules = [
            'email' => 'required|email',
        ];

        $messages = [
            'required' => 'This field is required.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            $request->flash();

            return redirect('/user/forgot-password')->withErrors($validator);
        }

        $email = $request->input('email');

        return redirect('/user/forgot-password-email-sent');
    }

    public function getForgotPasswordEmailSent()
    {
        return view('user/forgotPasswordEmailSent');
    }

}
