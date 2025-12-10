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
        Log::debug('UserController::postLogin: login attempt from user ' . $credentials['username']);

        if (! Auth::attempt($credentials)) {
            Log::debug('UserController::postLogin: invalid credentials for user ' . $credentials['username']);

            return redirect()->back()->withErrors(['Invalid credentials']);
        }

        $request->session()->regenerate();

        $user = Auth::user();
        $status = $user->getStatus();
        if ($status != 'Standard') {
            Log::debug('UserController::postLogin: user access for ' . $credentials['username'] . ' denied, status = ' . $status);

            return redirect()->back()->withErrors(['Login not allowed for user ' . $credentials['username'] . ' (status = ' . $status . '), contact support@ireceptor.org']);
        }

        if (! $user->did_survey) {
            return redirect('/ireceptor-survey');
        }

        Log::debug('UserController::postLogin: successful login from user ' . $credentials['username']);

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
        // Validate form to make sure we have required fields.
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

        // Get the data from the form
        $first_name = $request->get('first_name');
        $last_name = $request->get('last_name');
        $email = $request->get('email2');
        $country = $request->get('country');
        $institution = $request->get('institution');
        $notes = $request->get('notes');

        // Check it's not a bot
        $honey_pot_email = $request->get('email');
        if (Str::length($honey_pot_email) != 0) {
            Log::info('Bot account creation prevented: ' . $first_name . ' ' . $last_name . ' - ' . $email . ' - ' . $country . ' - ' . $institution);
            abort(403, 'Sorry, registration is not allowed to bots.');
        }

        // Generate a random string for a password
        $password = str_random(24);

        // Add the user information to the user database
        $u = User::add($first_name, $last_name, $email, $password, $country, $institution, $notes);

        // Send an email to the user about account creation
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

        // Email credentials
        try {
            Mail::send(['text' => 'emails.auth.accountCreated'], $t, function ($message) use ($u) {
                $message->to($u->email)->subject('iReceptor account');
            });
        } catch (\Exception $e) {
            Log::error('UserController::postRegister - Account creation email delivery failed');
            Log::error('UserController::postRegister - ' . $e->getMessage());
        }

        // Send an admin notification email about the new user.
        try {
            Mail::send(['text' => 'emails.auth.newUser'], $t, function ($message) use ($u) {
                $message->to(config('ireceptor.email_support'))->subject('New account - ' . $u->first_name . ' ' . $u->last_name);
            });
        } catch (\Exception $e) {
            Log::error('UserController::postRegister - Support email delivery failed');
            Log::error('UserController::postRegister - ' . $e->getMessage());
        }

        // Log the user in.
        Auth::login($u);

        // Take them to the welcome page.
        return redirect('/user/welcome');
    }

    public function getWelcome()
    {
        return view('user/welcome');
    }

    public function getForgotPassword($email = '')
    {
        Log::debug('UserContorller::getForgotPassword');
        $data['email'] = $email;

        return view('user/forgotPassword', $data);
    }

    public function postForgotPassword(Request $request)
    {
        Log::debug('UserContorller::postForgotPassword');

        // Validate form to ensure that the email id filled out
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

        // Get the email the user provided
        $email = $request->input('email');

        // Get the user - generate an error and redirect if the email is not for
        // a valid user.
        $user = User::where('email', $email)->first();
        if ($user == null) {
            $request->flash();

            return redirect()->back()->withErrors(['email' => 'Sorry, there\'s no user with this email address. Make sure to enter the email you registered with.']);
        }

        // Check the status of the user. If they are not allowed to log in,
        // redirect and generate an error.
        if ($user->getStatus() != 'Standard') {
            Log::debug('UserController::postForgotPassword: User with email ' . $email . ' has status ' . $user->getStatus() . ', can not change password');

            return redirect()->back()->withErrors(['email' => 'User with email ' . $email . ' has status ' . $user->getStatus() . ', can not change password, contact support@ireceptor.org']);
        }

        // Generate token for this password reset.
        $hashKey = config('app.key');
        $token = hash_hmac('sha256', Str::random(40), $hashKey);
        Log::debug('Forgotten password for ' . $user->username . ', token: ' . $token);

        // Add token to DB so that we can track it.
        $table = 'password_resets';
        DB::table($table)->where('email', $email)->delete();
        DB::table($table)->insert([
            'email' => $email,
            'token' => $token,
            'created_at' => Carbon::now(),
        ]);

        // Email reset link to the user. If the email fails, notify the user and
        // ask them to try later.
        $t = [];
        $t['reset_link'] = config('app.url') . '/user/reset-password/' . $token;
        $t['first_name'] = $user->first_name;
        try {
            Mail::send(['text' => 'emails.auth.resetPasswordLink'], $t, function ($message) use ($email) {
                $message->to($email)->subject('Reset your password');
            });
        } catch (\Exception $e) {
            Log::error('UserController::postForgotPassword - YYY User reset password email delivery failed');
            Log::error('UserController::ForgotPassword - ' . $e->getMessage());

            return redirect()->back()->withErrors(['email' => 'Sorry, we were unable to send the password reset email. Please try again later.']);
        }

        // Make sure we are logged out after password reset.
        auth()->logout();

        // Redirect to the email sent page.
        return redirect('/user/forgot-password-email-sent');
    }

    public function getForgotPasswordEmailSent()
    {
        // Inform the user that the reset email has been sent.
        Log::debug('UserContorller::getForgotPasswordEmailSent');

        return view('user/forgotPasswordEmailSent');
    }

    public function getResetPassword($reset_token)
    {
        // This handles when a user clicks on the token link
        // to generate a new password.
        Log::debug('UserContorller::getResetPassword - Token from email: ' . $reset_token);

        // Check token to make sure it is a valid reset token in the DB
        $table = 'password_resets';
        $entry = DB::table($table)->where('token', $reset_token)->first();
        if ($entry == null) {
            $data = [];
            $data['message'] = 'Sorry, your reset link is invalid.';
            $data['message2'] = 'Note: Microsoft Defender for Office 365 pre-visits links in emails by default, so your new password may have been sent to you by email already.';

            return response()->view('error', $data, 401);
        }
        // Get the user that is associated with the token.
        $user = User::where('email', $entry->email)->first();

        // Assign a new random password and save it in the user DB as a hash
        $new_password = str_random(24);
        $user->password = Hash::make($new_password);
        $user->save();

        // Email new temporary credential information to the user.
        $email = $user->email;
        $t = [];
        $t['first_name'] = $user->first_name;
        $t['username'] = $user->username;
        $t['password'] = $new_password;
        $t['login_link'] = config('app.url') . '/login';
        $t['reset_link'] = config('app.url') . '/user/change-password';
        try {
            Mail::send(['text' => 'emails.auth.newPassword'], $t, function ($message) use ($email) {
                $message->to($email)->subject('Your new iReceptor credentials');
            });
        } catch (\Exception $e) {
            Log::error('UserController::getResetPassword - User new password email delivery failed');
            Log::error('UserController::getResetPassword - ' . $e->getMessage());
        }
        Log::debug('UserContorller::getResetPassword - New credentials sent for user ' . $user->username);

        // Disable/remove reset token
        DB::table($table)->where('token', $reset_token)->delete();

        // Make sure we are logged out after password reset.
        auth()->logout();

        return redirect('/user/reset-password-confirmation');
    }

    public function getResetPasswordConfirmation()
    {
        // Inform the user that there password was reset.
        Log::debug('UserContorller::getResetPasswordConfirmation');

        return view('user/resetPasswordConfirmation');
    }
}
