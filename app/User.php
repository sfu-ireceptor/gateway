<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'user';

    // attributes that are mass assignable.
    protected $fillable = [
        'name', 'email', 'password', 'username', 'admin', 'galaxy_url', 'galaxy_tool_id', 'stats_popup_count', 'token',
    ];

    // attributes that should be hidden for arrays.
    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $dates = ['token_expiration_date'];

    public function isAdmin()
    {
        return $this->admin;
    }

    public static function exists($username)
    {
        $user = self::where('username', $username)->first();

        return $user != null;
    }

    public function generateUsername()
    {
        $first_name_stripped = str_replace(' ', '', $this->first_name);
        $last_name_stripped = str_replace(' ', '', $this->last_name);
        $username = strtolower($first_name_stripped) . '_' . strtolower($last_name_stripped);
        $username = iconv('UTF-8', 'ASCII//TRANSLIT', $username); // remove diacritics

        // if username already exists, append number
        if (self::exists($username)) {
            $i = 2;
            $alternate_username = $username . $i;
            while (self::exists($alternate_username)) {
                $i++;
                $alternate_username = $username . $i;
            }
            $username = $alternate_username;
        }

        return $username;
    }

    public static function add($first_name, $last_name, $email, $password, $country, $institution, $notes)
    {
        $user = new User();

        $user->first_name = $first_name;
        $user->last_name = $last_name;
        $user->email = $email;
        $user->country = $country;
        $user->institution = $institution;
        $user->notes = $notes;

        $user->username = $user->generateUsername();
        $user->password = Hash::make($password);

        $user->save();

        return $user;
    }

    public static function parseTapisUsersLDIF($filepath)
    {
        // it's slow because of the password hashing
        ini_set('max_execution_time', 180);

        $l = parse_ldif_file($filepath);
        foreach ($l as $t) {
            if (isset($t['uid'])) {
                $username = $t['uid'];
                $user = self::where('username', $username)->first();

                if ($user == null) {
                    Log::warning('User ' . $username . ' did not exist in local database, so creation time might be wrong.');

                    $user = new User();
                    $user->username = $username;
                    $user->email = '';
                    $user->first_name = '';
                    $user->last_name = '';
                    $user->password = '';
                }

                if (isset($t['mail'])) {
                    $user->email = $t['mail'];
                }

                if (isset($t['givenname'])) {
                    $user->first_name = $t['givenname'];
                }

                if (isset($t['sn'])) {
                    $user->last_name = $t['sn'];
                }

                if (isset($t['userpassword'])) {
                    $user->password = Hash::make(base64_decode($t['userpassword']));
                }

                $user->save();
            }
        }
    }

    /**
     * Get the token for the user.
     *
     * @param void
     * @return string
     */
    public function getToken()
    {
        // Check to see if we are close to token expiry
        // Expiry threshold is 30 minutes
        $expiry_threshold_min = 30;
        $now = Carbon::now();
        $expiry_threshold = Carbon::now()->addMinutes($expiry_threshold_min);
        Log::debug('User::getToken: user = ' . $this->username);
        Log::debug('User::getToken: now = ' . $now);
        Log::debug('User::getToken: expiry threshold = ' . $expiry_threshold);
        Log::debug('User::getToken: token expiration = ' . $this->token_expiration_date);
        // If we are not close to expiry (within threshold), just return the
        // current token.
        if ($this->token_expiration_date->gt($expiry_threshold)) {
            Log::debug('User::getToken: No refresh required');

            return $this->token;
        }

        // If we are within an hour, then request a new token and stores
        // it in the local token field.
        Log::debug('User::getToken: Requesting a new token');
        $agave = new Agave;
        $agave_token_info = $agave->renewToken($this->refresh_token);
        if ($agave_token_info != null) {
            // update the token
            $this->updateToken($agave_token_info);

            // Return the new token
            return $this->token;
        } else {
            return null;
        }
    }

    /**
     * Get the refresh token for the user.
     *
     * @param void
     * @return string
     */
    public function getRefreshToken()
    {
        return $this->refresh_token;
    }

    /**
     * Update the token state for the user.
     *
     * @param  object  $agave_token_info
     * @return void
     *
     * The Agave token info is of the form:
     * {
     *  "scope":"default","token_type":"bearer",
     *  "expires_in":14400,
     *  "refresh_token":"6256416cf0bfbd4163ff7e758ba22d93",
     *  "access_token":"873f7d1d9333a935ca4e3f487da7e34"
     * }
     *
     * We want to save some of this state for the user.
     */
    public function updateToken($agave_token_info)
    {
        // token
        $token = $agave_token_info->access_token;
        $this->token = $token;

        // refresh token
        $refreshToken = $agave_token_info->refresh_token;
        $this->refresh_token = $refreshToken;

        // token expiration date
        $tokenExpirationDate = new Carbon();
        $tokenExpirationDate->addSeconds($agave_token_info->expires_in);
        $this->token_expiration_date = $tokenExpirationDate;

        // Save the state
        $this->save();

        Log::debug('User::updateToken(' . $this->username . ') - access_token = ' . $this->token);
        Log::debug('User::updateToken(' . $this->username . ') - refresh_token = ' . $this->refresh_token);
        Log::debug('User::updateToken(' . $this->username . ') - expiration_date = ' . $this->token_expiration_date);
        // Return the user token
        return $this->token;
    }
}
