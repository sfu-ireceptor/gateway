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

    public static function getUser($username)
    {
        $user = self::where('username', $username)->first();

        return $user;
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
     * @return string Tapis 3 JWT token
     */
    public function getToken()
    {
        return $this->token;
        /*
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
        if ($this->token_expiration_date != null && $this->token_expiration_date->gt($expiry_threshold)) {
            Log::debug('User::getToken: No refresh required');

            return $this->token;
        }

        // If we are within an hour, then request a new token and stores
        // it in the local token field.
        Log::debug('User::getToken: Requesting a new token');
        $tapis = new Tapis;
        $tapis_token_info = $tapis->renewToken();
        if ($tapis_token_info != null) {
            Log::debug('User::getToken: tapis token info = ' . json_encode($tapis_token_info));
            // update the token
            $this->updateToken($tapis_token_info);

            // Return the new token
            return $this->token;
        } else {
            return null;
        }
         */
    }

    /**
     * Update the token state for the user.
     *
     * @param  object  $tapis_token_info
     * @return string Tapis 3 JWT token string
     *
     * The Tapis token info is of the form:
     * {"access_token":"JWT TOKEN INFO DELETED","expires_at":"2023-03-25T21:13:31.081319+00:00",
     *  "expires_in":14400,"jti":"38a395a2-7496-4349-89ef-afff5c5f69ad"}
     *
     * We want to save some of this state for the user.
     */
    public function updateToken($tapis_token_info)
    {
        // token
        $token = $tapis_token_info->access_token;
        $this->token = $token;

        // Refresh token is no longer supported by Tapis. We keep it in the DB
        // for now, but this should be removed. TODO
        $this->refresh_token = null;

        // token expiration date
        $tokenExpirationDate = new Carbon();
        $tokenExpirationDate->addSeconds($tapis_token_info->expires_in);
        $this->token_expiration_date = $tokenExpirationDate;

        // Save the state
        $this->save();

        Log::debug('User::updateToken(' . $this->username . ') - access_token = ' . $this->token);
        Log::debug('User::updateToken(' . $this->username . ') - expiration_date = ' . $this->token_expiration_date);
        // Return the user token
        return $this->token;
    }
}
