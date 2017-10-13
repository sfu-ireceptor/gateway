<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;

class CacheSample extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'samples';
    protected $guarded = [];

    public static function list($params)
    {
        $l = static::all();

        return $l;
    }
}
