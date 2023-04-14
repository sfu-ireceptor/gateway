<?php

namespace App\Http\Controllers;

use App\Agave;
use App\News;
use App\Sample;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function getLogin(Request $request)
    {
        $cached_data = Cache::get('login-data');
        if ($cached_data != null) {
            $data = $cached_data;
        } else {
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

            Cache::put('login-data', $data);
        }

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

        // TEMPORARY, to remove when switching to Tapis V3
        // generate new Agave token using iReceptor Admin credential and store it as the user's token
        $agave = new Agave;
        $admin_username = config('services.agave.admin_username');
        $admin_password = config('services.agave.admin_password');
        $agave_token_info = $agave->getTokenForUser($admin_username, $admin_password);

        if ($agave_token_info == null) {
            Log::error('Failed to get token for ' . $admin_username);
        } else {
            $user = Auth::user();
            $user->updateToken($agave_token_info);
        }

        $user = Auth::user();
        if (! $user->did_survey) {
            return redirect('/ireceptor-survey');
        }

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
        // validate form
        $rules = [
            'current_password' => 'required|current_password',
            'password' => 'required|min:6',
            'password_confirmation' => 'required|same:password',
        ];

        $messages = [
            'required' => 'Required.',
            'min' => 'Must have at least :min characters.',
            'same' => 'Didn\'t match.',
            'current_password' => 'Invalid.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            $request->flash();

            return redirect('user/change-password')->withErrors($validator);
        }

        $user = Auth::user();
        $user->password = Hash::make($request->input('password'));
        $user->save();

        return redirect('user/account')->with('notification', 'Your password was successfully changed.');
    }

    public function getAccount()
    {
        $user = Auth::user();

        $data = [];
        $data['user'] = $user;
        $data['notification'] = session('notification');

        return view('user/account', $data);
    }

    public function getChangePersonalInfo()
    {
        $user = Auth::user();

        $data = [];
        $data['first_name'] = $user->first_name;
        $data['last_name'] = $user->last_name;
        $data['email'] = $user->email;
        $data['notification'] = session('notification');

        return view('user/changePersonalInfo', $data);
    }

    public function postChangePersonalInfo(Request $request)
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

            return redirect('/user/change-personal-info')->withErrors($validator);
        }

        $user = Auth::user();
        $user->first_name = $request->input('first_name');
        $user->last_name = $request->input('last_name');
        $user->email = $request->input('email');
        $user->save();

        return redirect('/user/account')->with('notification', 'Personal information was successfully chaged.');
    }

    public function getForgotPassword()
    {
        return view('user/forgotPassword');
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

        $user = User::where('email', $email)->first();
        if ($user == null) {
            $request->flash();

            return redirect()->back()->withErrors(['email' => 'Sorry, there\'s no user with this email address. Make sure to enter the email you registered with.']);
        }

        // generate token
        $hashKey = config('app.key');
        $token = hash_hmac('sha256', Str::random(40), $hashKey);
        Log::debug('Forgotten password token: ' . $token);

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

        $user = User::where('email', $entry->email)->first();

        $new_password = str_random(24);
        $user->password = Hash::make($new_password);
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
