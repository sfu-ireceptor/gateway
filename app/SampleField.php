<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;

class SampleField extends Model
{
    protected $connection = 'mongodb'; // https://github.com/jenssegers/laravel-mongodb
    protected $collection = 'sample_fields';
    protected $guarded = [];

    // temporary way to create data
    public static function init()
    {
        $t = [];
        $t[] = ['airr' => 'study_id', 'v1' => 'project_id', 'v2' => 'study_id', 'airr_name' => 'Study ID', 'diplay_name' => 'Study'];
        $t[] = ['airr' => 'aa_id', 'v1' => 'bb_id', 'v2' => 'cc_id', 'airr_name' => 'AAAA BBBBB', 'diplay_name' => 'AAA'];
        
        foreach ($t as $sf) {
			self::create($sf);
        }

    }

    public static function distinctValues($fieldName)
    {
        $l = self::whereNotNull($fieldName)->distinct($fieldName)->get();
        $l = $l->toArray();

        // replace each array item (a one-item array) by the value directly
        // Ex: [0]=>[[0]=> "Unknown"] is replaced by [0]=> "Unknown"
        $t = [];
        foreach ($l as $lt) {
            $t[] = $lt[0];
        }

        return $t;
    }

    public static function distinctValuesGrouped($fields)
    {
        $l = self::groupBy($fields)->get();
        $l = $l->toArray();

        // remove useless '_id' key
        foreach ($l as $k => $v) {
            unset($v['_id']);
            $l[$k] = $v;
        }

        return $l;
    }
}
