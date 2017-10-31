<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FieldName extends Model
{
    protected $table = 'field_name';
    protected $guarded = [];

    // convert field names for 1 array
    public static function convert($data, $from, $to)
    {
        $mapping = self::all([$from, $to])->toArray();

        return convert_array_keys($data, $mapping, $from, $to);
    }

    // convert field names for a list of arrays
    public static function convertList($data, $from, $to)
    {
        $mapping = self::all([$from, $to])->toArray();
        // var_dump($mapping);die();

        return convert_arrays_keys($data, $mapping, $from, $to);
    }

    // convert field names for a list of objects
    public static function convertObjectList($data, $from, $to)
    {
        $mapping = self::all([$from, $to])->toArray();

        $array_list = convert_arrays_keys($data, $mapping, $from, $to);

        $object_list = [];
        foreach ($array_list as $a) {
            $object_list[] = (object) $a;
        }

        return $object_list;
    }
}
