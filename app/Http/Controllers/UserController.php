<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

use App\User;
use App\Agave;

class UserController extends Controller {

	public function getLogin()
	{
		return view('userLogin');
	}

	public function postLogin(Request $request)
	{
		$username = $request->input('username');
		$password = $request->input('password');

		// try to get Agave OAuth token
		$agave = new Agave;
		$t = $agave->getTokenForUser($username, $password);

		// if fail -> display form with error
		if ($t == NULL) {
	        return redirect()->back()->withErrors(array('Invalid credentials'));			
		}

        // create user in local DB if necessary
        $user = User::where('username', $username)->first();
        if($user == NULL)
        {
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
            $data = array();
            $data['notification'] = session('notification');

            return view('userChangePassword', $data);
    }

    public function postChangePassword(Request $request)
    {
        // custom form validation rule to check user's current password
        Validator::extend('current_password', function($field, $value, $parameters) {
            $username = auth()->user()->username;
            $password = $value;

            $agave = new Agave;
            $t = $agave->getTokenForUser($username, $password);
            
            return $t != NULL;
        });

        // validate form
        $rules = array(
            'current_password' => 'required|current_password',
            'password' => 'required|min:6',
            'password_confirmation' => 'required|same:password'
        );

        $messages = array(
            'required' => 'Required.',
            'min' => 'Must have at least :min characters.',
            'same' => 'Didn\'t match',
            'current_password' => 'Invalid'
        );

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails())
        {
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
        // var_dump(auth()->user());die();

        $username = auth()->user()->username;
        
        $agave = new Agave;
        $token = $agave->getAdminToken();
        $l = $agave->getUser($username, $token);
        $user = $l->result;

        //$user = auth()->user();
        // var_dump($user);

        $data = array();
        $data['user'] = $user;;
        $data['notification'] = session('notification');

        return view('userAccount', $data);
    }

    public function getChangePersonalInfo()
    {
        $agave = new Agave;
        $token = $agave->getAdminToken();

        $username = auth()->user()->username;
        $l = $agave->getUser($username, $token);
        $l = $l->result;

        $data = array();
        $data['username'] = $l->username;
        $data['first_name'] = $l->first_name;
        $data['last_name'] = $l->last_name;
        $data['email'] = $l->email;
        $data['notification'] = session('notification');

        return view('userChangePersonalInfo', $data);
    }

    public function postChangePersonalInfo(Request $request)
    {
        // validate form
        $rules = array(
            'username' => 'required',
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email|unique:user,username'
        );

        $messages = array(
            'required' => 'This field is required.',
        );

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails())
        {
            $request->flash();
            return redirect('/user/change-personal-info')->withErrors($validator);
        }

        $username = auth()->user()->username;
        $firstName = $request->input('first_name');
        $lastName = $request->input('last_name');
        $email = $request->input('email');

        // create Agave account
        $agave = new Agave;
        $token = $agave->getAdminToken();
        $t = $agave->updateUser($token, $username, $firstName, $lastName, $email);
        
        return redirect('/user/account')->with('notification', 'Personal information was successfully chaged.');
    } 
}
