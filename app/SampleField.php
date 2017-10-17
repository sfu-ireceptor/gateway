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
        $t[] = ['airr' => 'person_id', 'v1' => 'subject_id', 'v2' => 'person_id', 'airr_name' => 'Person', 'diplay_name' => 'Person'];

        foreach ($t as $sf) {
            self::create($sf);
        }
    }

    // convert keys for 1 sample
    // ex: [project_id => 1, subject_id => 2] to [study_id => 1, subject_id => 2]
    public static function convertItem($data, $from, $to)
    {
        $mapping = self::all()->toArray();

        $t = [];
        foreach ($data as $key => $value) {
            $converted = false;

            foreach ($mapping as $m) {
                if (isset($m[$from]) && $m[$from] == $key) {
                    if (isset($m[$to])) {
                        $t[$m[$to]] = $value;
                        $converted = true;
                        break;
                    }
                }
            }

            // no mapping found for this field name
            if ($converted == false) {
                $t[$key] = $value;
            }
        }

        return $t;
    }

    // convert keys for a list of samples
    public static function convert($data, $from, $to)
    {
        $t = [];
        foreach ($data as $d) {
            $t[] = self::convertItem($d, $from, $to);
        }

        return $t;
    }

}
