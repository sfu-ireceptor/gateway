<?php

namespace App\Http\Controllers;

use App\Agave;
use App\News;
use App\Sample;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function getLogin(Request $request)
    {
        // get count of available data (sequences, samples)
        $username = 'titi';
        $metadata = Sample::metadata($username);
        $data = $metadata;

        $sample_list = Sample::public_samples();

        // Fields we want to graph. The UI/blade expects six fields
        $charts_fields = ['study_type_id', 'organism', 'disease_diagnosis_id',
            'tissue_id', 'pcr_target_locus', 'template_class', ];
        // Mapping of fields to display as labels on the graph for those that need
        // mappings. These are usually required for ontology fields where we want
        // to aggregate on the ontology ID but display the ontology label.
        $field_map = ['study_type_id' => 'study_type',
            'disease_diagnosis_id' => 'disease_diagnosis',
            'tissue_id' => 'tissue', ];
        $data['charts_data'] = Sample::generateChartsData($sample_list, $charts_fields, $field_map);

        // generate statistics
        $sample_data = Sample::stats($sample_list);
        $data['rest_service_list'] = $sample_data['rs_list'];

        $metadata = Sample::metadata();
        $data['total_repositories'] = $metadata['total_repositories'];
        $data['total_labs'] = $metadata['total_labs'];
        $data['total_studies'] = $metadata['total_projects'];
        $data['total_samples'] = $metadata['total_samples'];
        $data['total_sequences'] = $metadata['total_sequences'];

        $data['news'] = News::orderBy('created_at', 'desc')->first();
        $data['is_login_page'] = true;

        return view('user/login', $data);
    }

    public function postLogin(Request $request)
    {
        $credentials = $request->only('username', 'password');

        if (! Auth::attempt($credentials)) {
            return redirect()->back()->withErrors(['Invalid credentials']);
        }

        $request->session()->regenerate();

        return redirect()->intended('home');
    }

    public function getLogout()
    {
        auth()->logout();

        return redirect('login');
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

            return redirect()->back()->withErrors($validator);
        }

        $email = $request->input('email');
        $agave = new Agave;
        $token = $agave->getAdminToken();
        $user = $agave->getUserWithEmail($email, $token);
        if ($user == null) {
            $request->flash();

            return redirect()->back()->withErrors(['email' => 'Sorry, we could not find an iReceptor user with this email address.']);
        }

        // generate token
        $hashKey = config('app.key');
        $token = hash_hmac('sha256', Str::random(40), $hashKey);
        Log::debug('Token: ' . $token);

        // add token to DB
        $table = 'password_resets';
        DB::table($table)->where('email', $email)->delete();
        DB::table($table)->insert([
            'email' => $email,
            'token' => $token,
            'created_at' => Carbon::now(),
        ]);

        // email reset link
        $t = [];
        $t['reset_link'] = config('app.url') . '/user/reset-password/' . $token;
        $t['first_name'] = $user->first_name;
        Mail::send(['text' => 'emails.auth.resetPasswordLink'], $t, function ($message) use ($email) {
            $message->to($email)->subject('Reset your password');
        });

        return redirect('/user/forgot-password-email-sent');
    }

    public function getForgotPasswordEmailSent()
    {
        return view('user/forgotPasswordEmailSent');
    }

    public function getResetPassword($reset_token)
    {
        Log::debug('Token from email: ' . $reset_token);

        // check token
        $table = 'password_resets';
        $entry = DB::table($table)->where('token', $reset_token)->first();
        if ($entry == null) {
            $data = [];
            $data['message'] = 'Sorry, your reset link is invalid.';
            $data['message2'] = 'Note: Microsoft Defender for Office 365 pre-visits links in emails by default, so your new password may have been sent to you by email already.';

            return response()->view('error', $data, 401);
        }

        // find user
        $agave = new Agave;
        $token = $agave->getAdminToken();
        // echo $entry->email;die();
        $agave_user = $agave->getUserWithEmail($entry->email, $token);

        // update user passord in Agave
        $new_password = str_random(24);
        $t = $agave->updateUser($token, $agave_user->username, $agave_user->first_name, $agave_user->last_name, $agave_user->email, $new_password);

        Log::debug('New password: ' . $new_password);

        // create user in local DB if necessary
        $user = User::where('username', $agave_user->username)->first();
        if ($user == null) {
            $user = new User();
        }

        // update user info in local DB
        $user->username = $agave_user->username;
        $user->first_name = $agave_user->first_name;
        $user->last_name = $agave_user->last_name;
        $user->email = $agave_user->email;

        $user->save();

        // log user in
        auth()->login($user);

        // email new password
        $email = $user->email;
        $t = [];
        $t['first_name'] = $user->first_name;
        $t['username'] = $user->username;
        $t['password'] = $new_password;
        $t['login_link'] = config('app.url') . '/login';
        Mail::send(['text' => 'emails.auth.newPassword'], $t, function ($message) use ($email) {
            $message->to($email)->subject('Your new password');
        });

        // disable reset token
        DB::table($table)->where('token', $reset_token)->delete();

        return redirect('/user/reset-password-confirmation');
    }

    public function getResetPasswordConfirmation()
    {
        return view('user/resetPasswordConfirmation');
    }
}
