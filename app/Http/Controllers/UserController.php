<?php

namespace App\Http\Controllers;

use Adrianorosa\GeoLocation\GeoLocation;
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
        //$cached_data = null;
        if ($cached_data != null) {
            $data = $cached_data;
        } else {
            // get count of available data (sequences, samples)
            $username = 'titi';
            // Metadata is essentially a list of fields and the possible data
            // values that those fields can take on. The fields are those that
            // are contorlled vocabulary fields or ontology fields in the AIRR
            // Standard.
            // E.g. metadata = {"template_class":["DNA","RNA","cDNA"],
            // "pcr_target_locus":["IGH","IGK","IGL","TRA","TRB","TRD"] ... } etc
            $metadata = Sample::metadata($username);
            $data = $metadata;

            // Get the cached sequence public samples
            $sample_list = Sample::public_samples('sequence');

            // Generate the rest service list info for this query. This has the
            // sample tree info required for our study browsing.
            $sample_data = Sample::stats($sample_list);
            $data['rest_service_list_sequences'] = $sample_data['rs_list'];

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

            // Get the cached clone public_samples
            $sample_list = Sample::public_samples('clone');

            // Generate the rest service list info for this query. This has the
            // sample tree info required for our study browsing.
            $sample_data = Sample::stats($sample_list, 'ir_clone_count');
            $data['rest_service_list_clones'] = $sample_data['rs_list'];

            // Clone Fields we want to graph. The UI/blade expects six fields
            $charts_fields = ['study_type_id', 'organism', 'disease_diagnosis_id',
                'tissue_id', 'pcr_target_locus', 'template_class', ];
            // Mapping of fields to display as labels on the graph for those that need
            // mappings. These are usually required for ontology fields where we want
            // to aggregate on the ontology ID but display the ontology label.
            $field_map = ['disease_diagnosis_id' => 'disease_diagnosis',
                'tissue_id' => 'tissue', ];
            $data['clone_charts_data'] = Sample::generateChartsData($sample_list, $charts_fields, $field_map, 'ir_clone_count');

            // Get the cached cell public_samples
            $sample_list = Sample::public_samples('cell');

            // Generate the rest service list info for this query. This has the
            // sample tree info required for our study browsing.
            $sample_data = Sample::stats($sample_list, 'ir_cell_count');
            $data['rest_service_list_cells'] = $sample_data['rs_list'];

            // Cell fields we want to graph. The UI/blade expects six fields
            $charts_fields = ['disease_diagnosis_id', 'tissue_id', 'cell_subset', 'disease_diagnosis_id', 'tissue_id', 'cell_subset'];
            // Mapping of fields to display as labels on the graph for those that need
            // mappings. These are usually required for ontology fields where we want
            // to aggregate on the ontology ID but display the ontology label.
            $field_map = ['disease_diagnosis_id' => 'disease_diagnosis',
                'tissue_id' => 'tissue', ];
            $data['cell_charts_data'] = Sample::generateChartsData($sample_list, $charts_fields, $field_map, 'ir_cell_count');

            // Temporarily store this the old way. This should not be required.
            $data['rest_service_list'] = $data['rest_service_list_sequences'];

            /* I don't think this is required - $data = Sample::metadata(); from above.
            $metadata = Sample::metadata();
            $data['total_repositories'] = $metadata['total_repositories'];
            $data['total_labs'] = $metadata['total_labs'];
            $data['total_studies'] = $metadata['total_projects'];
            $data['total_samples'] = $metadata['total_samples'];
            $data['total_sequences'] = $metadata['total_sequences'];
            */

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
        $data['country'] = $user->country;
        $data['institution'] = $user->institution;
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
        $user->country = $request->input('country');
        $user->institution = $request->input('institution');
        $user->save();

        return redirect('/user/account')->with('notification', 'Personal information was successfully chaged.');
    }

    public function getRegister(Request $request)
    {
        $ip = $request->getClientIp();
        $ip_info = GeoLocation::lookup($ip);
        $country = $ip_info->getCountry();

        $data = [];
        $data['country'] = $country;

        return view('user/register', $data);
    }

    public function postRegister(Request $request)
    {
        // validate form
        $rules = [
            'first_name' => 'required',
            'last_name' => 'required',
            'email2' => 'required|email|unique:user,email',
        ];

        $messages = [
            'required' => 'This field is required.',
            'unique' => 'This account already exists',
            'email' => 'Must be a valid email',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            $request->flash();

            return redirect('/register')->withErrors($validator);
        }

        $first_name = $request->get('first_name');
        $last_name = $request->get('last_name');
        $email = $request->get('email2');
        $country = $request->get('country');
        $institution = $request->get('institution');
        $notes = $request->get('notes');

        // check it's not a bot
        $honey_pot_email = $request->get('email');
        if (Str::length($honey_pot_email) != 0) {
            Log::info('Bot account creation prevented: ' . $first_name . ' ' . $last_name . ' - ' . $email . ' - ' . $country . ' - ' . $institution);
            abort(403, 'Sorry, registration is not allowed to bots.');
        }

        $password = str_random(24);

        $u = User::add($first_name, $last_name, $email, $password, $country, $institution, $notes);

        $t = [];
        $t['app_url'] = config('app.url');
        $t['first_name'] = $u->first_name;
        $t['username'] = $u->username;
        $t['password'] = $password;
        $t['last_name'] = $u->last_name;
        $t['email'] = $u->email;
        $t['notes'] = $u->notes;
        $t['country'] = $u->country;
        $t['institution'] = $institution;

        // email credentials
        try {
            Mail::send(['text' => 'emails.auth.accountCreated'], $t, function ($message) use ($u) {
                $message->to($u->email)->subject('iReceptor account');
            });
        } catch (\Exception $e) {
            Log::error('UserController::postRegister - Account creation email delivery failed');
            Log::error('UserController::postRegister - ' . $e->getMessage());
        }

        // admin notification email
        try {
            Mail::send(['text' => 'emails.auth.newUser'], $t, function ($message) use ($u) {
                $message->to(config('ireceptor.email_support'))->subject('New account - ' . $u->first_name . ' ' . $u->last_name);
            });
        } catch (\Exception $e) {
            Log::error('UserController::postRegister - Support email delivery failed');
            Log::error('UserController::postRegister - ' . $e->getMessage());
        }

        Auth::login($u);

        return redirect('/user/welcome');
    }

    public function getWelcome()
    {
        return view('user/welcome');
    }

    public function getForgotPassword($email = '')
    {
        $data['email'] = $email;

        return view('user/forgotPassword', $data);
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
        try {
            Log::info('UserController::postForgotPassword - Sending user reset password email to ' . $email);
            Mail::send(['text' => 'emails.auth.resetPasswordLink'], $t, function ($message) use ($email) {
                $message->to($email)->subject('Reset your password');
            });
            Log::info('UserController::postForgotPassword - Done sending user reset password email to ' . $email);
        } catch (\Exception $e) {
            Log::error('UserController::postForgotPassword - YYY User reset password email delivery failed');
            Log::error('UserController::ForgotPassword - ' . $e->getMessage());

            return redirect()->back()->withErrors(['email' => 'Sorry, we were unable to send the password reset email. Make sure to enter the email you registered with.']);
        }

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
        try {
            Mail::send(['text' => 'emails.auth.newPassword'], $t, function ($message) use ($email) {
                $message->to($email)->subject('Your new password');
            });
        } catch (\Exception $e) {
            Log::error('UserController::getResetPassword - User new password email delivery failed');
            Log::error('UserController::getResetPassword - ' . $e->getMessage());
        }

        // disable reset token
        DB::table($table)->where('token', $reset_token)->delete();

        return redirect('/user/reset-password-confirmation');
    }

    public function getResetPasswordConfirmation()
    {
        return view('user/resetPasswordConfirmation');
    }
}
