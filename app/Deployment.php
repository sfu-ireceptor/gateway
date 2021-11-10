<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Deployment extends Model
{
    protected $table = 'deployment';
    protected $guarded = ['id'];
}
