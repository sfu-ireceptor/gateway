<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;

class CellCount extends Model
{
    protected $connection = 'mongodb'; // https://github.com/jenssegers/laravel-mongodb
    protected $collection = 'cell_counts';
    protected $guarded = [];
}
