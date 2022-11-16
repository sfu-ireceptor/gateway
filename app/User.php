<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'user';

    // attributes that are mass assignable.
    protected $fillable = [
        'name', 'email', 'password', 'username', 'admin', 'galaxy_url', 'galaxy_tool_id', 'stats_popup_count',
    ];

    // attributes that should be hidden for arrays.
    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $dates = ['token_expiration_date'];

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
        $expiry_threshold = Carbon::now()->subMinute($expiry_threshold_min);
        Log::debug('User::getToken: now = ' . $now);
        Log::debug('User::getToken: expiry threshold = ' . $expiry_threshold);
        Log::debug('User::getToken: token expiration = ' . $this->token_expiration_date);
        // If we are not close to expiry (within an hour), just return the
        // current token.
        if ($this->token_expiration_date->gt($expiry_threshold)) {
            Log::debug('User::getToken: No refresh required');

            return $this->password;
        }

        // If we are within an hour, then request a new token and stores
        // it in the local password field.
        Log::debug('User::getToken: Requesting a new token');
        $agave = new Agave;
        $agave_token_info = $agave->renewToken($this->refresh_token);
        $this->updateToken($agave_token_info);

        // Return the new password
        return $this->password;
    }

    /**
     * Get the refresh token for the user.
     *
     * @param void
     * @return string
     */
    public function getRefreshToken()
    {
        // The token is saved in the password field.
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
        $this->password = $token;

        // refresh token
        $refreshToken = $agave_token_info->refresh_token;
        $this->refresh_token = $refreshToken;

        // token expiration date
        $tokenExpirationDate = new Carbon();
        $tokenExpirationDate->addSeconds($agave_token_info->expires_in);
        $this->token_expiration_date = $tokenExpirationDate;

        // Save the state
        $this->save();

        Log::debug('User::updateToken(' . $this->username . ') - access_token = ' . $this->password);
        Log::debug('User::updateToken(' . $this->username . ') - refresh_token = ' . $this->refresh_token);
        Log::debug('User::updateToken(' . $this->username . ') - expiration_date = ' . $this->token_expiration_date);
        // Return the token, stored as the user password.
        return $this->password;
    }

    public function isAdmin()
    {
        return $this->admin;
    }
}
