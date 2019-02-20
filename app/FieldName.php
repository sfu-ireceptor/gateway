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

    public static function getSampleFields()
    {
        $l = static::where('ir_class', '=', 'repertoire')->orderBy('default_order', 'asc')->get()->toArray();

        return $l;
    }

    public static function getSequenceFields()
    {
        $l = static::where('ir_class', '=', 'rearrangement')->orderBy('default_order', 'asc')->get()->toArray();

        return $l;
    }

    public static function getSampleFieldsGrouped()
    {
        $l = static::where('ir_class', '=', 'repertoire')->orderBy('ir_subclass', 'asc')->orderBy('ir_short', 'asc')->get()->toArray();
        $groups = static::getGroups();

        $gl = [];
        foreach ($l as $t) {
            if( ! isset($gl[$t['ir_subclass']])) {
                $gl[$t['ir_subclass']] = ['name' => $groups[$t['ir_subclass']], 'fields' => []];
            }
            $gl[$t['ir_subclass']]['fields'][] = $t;
        }

        return $gl;
    }

    public static function getGroups()
    {
        $l = [];
        
        $l['study'] = 'Study';
        $l['subject'] = 'Subject';
        $l['diagnosis'] = 'Diagnosis';
        $l['sample'] = 'Sample';
        $l['cell_processing'] = 'Cell Processing';
        $l['nucleic_acid_processing'] = 'Nucleic Acid Processing';
        $l['sequencing_run'] = 'Sequencing Run';
        $l['software_processing'] = 'Software Processing';
        $l['ir_metadata'] = 'iReceptor Metadata';
        $l['other'] = 'Other';
        $l['ir_parameter'] = 'iReceptor Parameter';
        $l['rearrangement'] = 'Rearrangement';
        $l['ir_rearrangement'] = 'iReceptor Rearrangement';

        return $l;
    }
}
