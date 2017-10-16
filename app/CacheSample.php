<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;

class CacheSample extends Model
{
    protected $connection = 'mongodb'; // https://github.com/jenssegers/laravel-mongodb
    protected $collection = 'samples';
    protected $guarded = [];

    public static function list($params)
    {
        $l = static::all();

        return $l;
    }

    public static function distinctValues($fieldName)
    {
        $l = self::whereNotNull($fieldName)->distinct($fieldName)->get();
        $l = $l->toArray();

        // replace each array item (a one-item array) by the value directly
        // Ex: [0]=>[[0]=> "Unknown"] is replaced by [0]=> "Unknown"
        $t = [];
        foreach ($l as $lt) {
            $t[] = $lt[0];
        }

        return $t;
    }
}
