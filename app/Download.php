<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Download extends Model
{
    protected $table = 'download';
    protected $guarded = ['id'];
}
