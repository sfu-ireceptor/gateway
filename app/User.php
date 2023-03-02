<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

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

    public static function add($first_name, $last_name, $email, $password)
    {
        $user = new User();

        $user->first_name = $first_name;
        $user->last_name = $last_name;
        $user->email = $email;

        $user->username = $user->generateUsername();
        $user->password = Hash::make($password);

        $user->save();

        return $user;
    }

}
