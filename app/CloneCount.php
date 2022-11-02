<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;

class CloneCount extends Model
{
    protected $connection = 'mongodb'; // https://github.com/jenssegers/laravel-mongodb
    protected $collection = 'clone_counts';
    protected $guarded = [];
}
