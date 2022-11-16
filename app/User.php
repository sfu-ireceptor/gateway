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
    }

    public function isAdmin()
    {
        return $this->admin;
    }
}
