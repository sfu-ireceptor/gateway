<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Query extends Model
{
    protected $table = 'query';
    protected $fillable = ['params', 'duration', 'page'];

    public static function saveParams($params, $page)
    {
        $t = [];
        $t['params'] = json_encode($params);
        $t['page'] = $page;

        $q = static::create($t);

        return $q->id;
    }

    public static function getParams($id)
    {
        $q = static::find($id);

        return json_decode($q->params, true);
    }
}
