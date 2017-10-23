<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SampleField extends Model
{
    protected $table = 'sample_field';
    protected $guarded = [];

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
        // var_dump($mapping);die();

        return convert_arrays_keys($data, $mapping, $from, $to);
    }
}
