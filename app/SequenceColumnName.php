<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
 
class SequenceColumnName extends Model {
	protected $table = 'sequence_column_name';
	protected $fillable = array('name', 'title', 'enabled');

    public static function findEnabled()
    {
        $l = static::where('enabled', '=', true)->get();
        return $l;
    }
}
