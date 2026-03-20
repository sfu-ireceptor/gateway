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

    // Check to see if the given user has access to a specific resource type.
    // Essentially, ACLs are encoded in this function. This is based on the
    // the User's status ($this->status) and the type of resource. Examples of
    // the resource types would be "login", "sequence" (for sequence queries),
    // "download", and "job" etc.
    public function hasAccess($resource_type)
    {
        // If the user is an Admin user then can access all resources.
        if ($this->isAdmin()) {
            return true;
        }

        // Handle the case where the User doesn't have a status.
        // In the future this should probably return a very limited
        // status, but for now we want to be permissive as we transition
        $status_level = $this->getStatus();
        Log::debug('User::hasAccess: username = ' . $this->username);
        Log::debug('User::hasAccess: is admin = ' . $this->admin);
        Log::debug('User::hasAccess: status level = ' . $status_level);
        Log::debug('User::hasAccess: resource type = ' . $resource_type);

        // Handle login ACL checks
        if ($resource_type == 'login') {
            // Check user status levels for login access
            if ($status_level == 'Standard') {
                Log::debug('User::hasAccess: login Standard allowed');

                return true;
            } elseif ($status_level == 'Commercial') {
                Log::debug('User::hasAccess: login Commercial allowed');

                return true;
            }
            // If no other clauses asses to true, return false, user
            // can't log in.
            Log::debug('User::hasAccess: login denied');

            return false;
        }
        if ($resource_type == 'downloads') {
            return true;
        }
        if ($resource_type == 'jobs') {
            return true;
        }
        if ($resource_type == 'samples') {
            return true;
        }
        if ($resource_type == 'samples/cell') {
            return true;
        }
        if ($resource_type == 'samples/clone') {
            return true;
        }
        if ($resource_type == 'sequences') {
            return true;
        }
        if ($resource_type == 'clones') {
            return true;
        }
        if ($resource_type == 'cells') {
            return true;
        }
        if ($resource_type == 'sequences-quick-search') {
            return true;
        }
        // If we are asked for access to an unknown resource, err on
        // the side of not providing access.
        Log::error('User::hasAccess: Invalid resource type ' . $resource_type);

        return false;
    }

    // Check to see if the given user has access to based on query ID.
    // Essentially, ACLs for access to specific queries based on user
    // ID and type of user (admin vs normal) are encoded in this function.
    // This primarily checks if a user made a query, and if so, grants
    // access, while denying access to other users.
    public function hasAccessQueryID($resource_type, $gateway_query_id)
    {
        // If we are an Admin user, we are allowed to access the query.
        if ($this->isAdmin()) {
            Log::debug('User::hasAccessQueryID - access allowed for admin user ' . $this->username);

            return true;
        }

        // Check if the user has access to the resource type
        if (! $this->hasAccess($resource_type)) {
            return false;
        }

        // Get info about the query if it has a "done" status from the
        // logs based on the gateway URL query_id. We don't care about
        // queries that are not of "done" status, because there can be
        // many queries attemted that failed as we log failed query
        // attempts
        $query_array = QueryLog::find_gateway_query_url_query_id($gateway_query_id, $resource_type, 'done');

        // If there is no query in the database that has a done status,
        // then providing access to the query makes no sense. We need
        // to check for the special case that the query doesn't exist
        // at all as a 'done' query, which means that the query_id can
        // be used in a new query.
        $query_count = count($query_array);
        if ($query_count == 0) {
            // Check to see if the query_id is used in more than one
            // resource type (e.g. samples and sequences). This should
            // never happen and indicates a buggy/corrupt query, so we
            // disallow access to such a query. We get the full list of
            // queries for all resource types, and then get the list of
            // queries from the requested resource type. If the size is
            // not the same, then this indicates a bad query_id.
            $query_array = QueryLog::find_gateway_query_url_query_id($gateway_query_id);
            $resource_query_array = QueryLog::find_gateway_query_url_query_id($gateway_query_id, $resource_type);
            if (count($query_array) != count($resource_query_array)) {
                Log::debug('User::hasAccessQueryID - corrupt query_id = ' . $gateway_query_id . ', spans different resource types.');
                Log::debug('User::hasAccessQueryID - return lengths = ' . count($query_array) . ',' . count($resource_query_array));

                return false;
            }
            // If we get here, there is no "done" query for this query_id,
            // which means it is a valid query_id to use.
            Log::debug('User::hasAccessQueryID - could not find ' . $resource_type . ' query ' . $gateway_query_id . ', new gateway query allowed');

            return true;
        }

        // We now need to check if the user has access. This is indicated by the
        // query having a done state issued by the current user.
        // Username of the current user
        $username = $this->username;
        Log::debug('User::hasAccessQueryID - user = ' . $username);

        // Loop over each query
        foreach ($query_array as $query_info) {
            Log::debug('User::hasAccessQueryID - query_info = ' . $query_info->url);

            // Username that issued the query
            $query_user = $query_info->username;
            Log::debug('User::hasAccessQueryID - query owner = ' . $query_user);
            // If the user has accessed this query in the past, then it is allowed
            if ($username == $query_user) {
                Log::debug('User::hasAccessQueryID - access allowed for ' . $username);

                return true;
            }
        }

        // If we get here, then the user is not on the done list, deny access
        return false;
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
