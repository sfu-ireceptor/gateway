<?php

namespace App;

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
    // TODO: Token per user is no longer used in Tapis 3 implementation. This should
    // be removed from the database at some point. Leaving for now in case need to
    // revisit this need.
    protected $fillable = [
        'name', 'email', 'password', 'username', 'admin', 'galaxy_url', 'galaxy_tool_id', 'stats_popup_count', 'token', 'status',
    ];

    // attributes that should be hidden for arrays.
    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'token_expiration_date' => 'datetime',
    ];

    public function isAdmin()
    {
        return $this->admin;
    }

    public function getStatus()
    {
        if ($this->status == null) {
            return 'Standard';
        } else {
            return $this->status;
        }
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
        $user->status = 'Standard';

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
}
