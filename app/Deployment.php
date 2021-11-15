<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Deployment extends Model
{
    protected $table = 'deployment';
    protected $guarded = ['id'];
}
