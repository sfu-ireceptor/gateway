<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;

class Species extends Model
{
    protected $connection = 'mongodb'; // https://github.com/jenssegers/laravel-mongodb
    protected $collection = 'species';
    protected $guarded = [];
}
