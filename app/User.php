<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Carbon\Carbon;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'user';

    // attributes that are mass assignable.
    protected $fillable = [
        'name', 'email', 'password', 'username', 'admin',
    ];

    // attributes that should be hidden for arrays.
    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $dates = ['token_expiration_date',];

    public function updateToken($t)
    {
        // token
        $token = $t->access_token;
        $this->password = $token;
        
        // refresh token
        $refreshToken = $t->refresh_token;
        $this->refresh_token = $refreshToken;       

        // token expiration date
        $tokenExpirationDate = new Carbon();
        $tokenExpirationDate->addSeconds($t->expires_in);
        $this->token_expiration_date = $tokenExpirationDate;

        $this->save();
    }

    public function isAdmin()
    {
        return $this->admin;
    }
}
