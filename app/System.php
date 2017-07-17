<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class System extends Model
{
    protected $table = 'user_system';
    protected $fillable = ['user_id', 'name', 'host', 'username'];

    public static function select($id)
    {
        $system = static::find($id);

        // unselect all systems (for that user)
        static::where('user_id', '=', $system->user_id)->update(['selected' => false]);

        // select system
        $system->selected = true;
        $system->save();
    }

    public static function getCurrentSystem($user_id)
    {
        $system = static::where('user_id', $user_id)->where('selected', true)->first();

        return $system;
    }

    public static function get($id, $user_id)
    {
        $system = static::where('user_id', 1)->where('id', $id)->first();

        return $system;
    }
}
