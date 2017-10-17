<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;

class SampleField extends Model
{
    protected $connection = 'mongodb'; // https://github.com/jenssegers/laravel-mongodb
    protected $collection = 'sample_fields';
    protected $guarded = [];

    // temporary way to create a dataset
    public static function init()
    {
        $t = [];
        $t[] = ['airr' => 'study_id', 'v1' => 'project_id', 'v2' => 'study_id', 'airr_name' => 'Study ID', 'diplay_name' => 'Study'];
        $t[] = ['airr' => 'aa_id', 'v1' => 'bb_id', 'v2' => 'cc_id', 'airr_name' => 'AAAA BBBBB', 'diplay_name' => 'AAA'];
        $t[] = ['airr' => 'person_id', 'v1' => 'subject_id', 'v2' => 'person_id', 'airr_name' => 'Person', 'diplay_name' => 'Person'];

        foreach ($t as $sf) {
            self::create($sf);
        }
    }

    // convert field names for a list of samples
    public static function convert($data, $from, $to)
    {
        $mapping = self::all()->toArray();
        return convert_arrays_keys($data, $mapping, $from, $to);
    }

}
