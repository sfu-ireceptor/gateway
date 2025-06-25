<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;

class Antigens extends Model
{
    protected $connection = 'mongodb'; // https://github.com/jenssegers/laravel-mongodb
    protected $collection = 'antigens';
    protected $guarded = [];
}
