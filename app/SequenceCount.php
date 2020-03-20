<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;

class SequenceCount extends Model
{
    protected $connection = 'mongodb'; // https://github.com/jenssegers/laravel-mongodb
    protected $collection = 'sequence_counts';
    protected $guarded = [];

}
