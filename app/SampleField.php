<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SampleField extends Model
{
    protected $table = 'sample_field';
    protected $guarded = [];

    // // temporary way to create a dataset
    // public static function init()
    // {
    //     $t = [];
    //     $t[] = ['airr' => 'study_id', 'v1' => 'project_id', 'v2' => 'study_id', 'airr_name' => 'Study ID', 'diplay_name' => 'Study'];
    //     $t[] = ['airr' => 'aa_id', 'v1' => 'bb_id', 'v2' => 'cc_id', 'airr_name' => 'AAAA BBBBB', 'diplay_name' => 'AAA'];
    //     $t[] = ['airr' => 'person_id', 'v1' => 'subject_id', 'v2' => 'person_id', 'airr_name' => 'Person', 'diplay_name' => 'Person'];

    //     foreach ($t as $sf) {
    //         self::create($sf);
    //     }
    // }

    // convert field names for 1 sample
    public static function convertSample($data, $from, $to)
    {
        $mapping = self::all()->toArray();

        return convert_array_keys($data, $mapping, $from, $to);
    }

    // convert field names for a list of samples
    public static function convertSamples($data, $from, $to)
    {
        $mapping = self::all()->toArray();

        return convert_arrays_keys($data, $mapping, $from, $to);
    }
}
