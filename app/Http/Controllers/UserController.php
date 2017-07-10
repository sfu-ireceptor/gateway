<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
}
