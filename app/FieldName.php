<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

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

    public static function getGroups()
    {
        $l = [];

        $l['subject'] = 'Subject';
        $l['study'] = 'Study';
        $l['sample'] = 'Sample';

        $l['diagnosis'] = 'Diagnosis';
        $l['cell_processing'] = 'Cell Processing';
        $l['nucleic_acid_processing'] = 'Nucleic Acid Processing';
        $l['sequencing_run'] = 'Sequencing Run';
        $l['software_processing'] = 'Software Processing';
        $l['ir_metadata'] = 'iReceptor Metadata';
        $l['ir_parameter'] = 'iReceptor Parameter';
        $l['ir_api'] = 'iReceptor API';
        $l['ir_curator'] = 'iReceptor Curator';
        $l['rearrangement'] = 'Rearrangement';
        $l['ir_rearrangement'] = 'iReceptor Rearrangement';
        $l['other'] = 'Other';

        return $l;
    }

    public static function getFieldsGrouped($ir_class_list)
    {
        $l = static::whereIn('ir_class', $ir_class_list)->orderBy('ir_subclass', 'asc')->orderBy('ir_short', 'asc')->get()->toArray();
        $groups = static::getGroups();

        $gl = [];
        foreach ($groups as $group_key => $group_name) {
            foreach ($l as $t) {
                // if ir_subclass is not known, log warning and override it to 'other'
                if(! isset($groups[$t['ir_subclass']])) {
                    Log::warn($t['ir_subclass']  . ' ir_subclass needs to be defined as a group in ' . static::class);
                    $t['ir_subclass'] = 'other';
                }

                if ($group_key == $t['ir_subclass']) {
                    if (! isset($gl[$t['ir_subclass']])) {
                        $gl[$t['ir_subclass']] = ['name' => $groups[$t['ir_subclass']], 'fields' => []];
                    }
                    $gl[$t['ir_subclass']]['fields'][] = $t;
                }
            }
        }

        return $gl;
    }

    public static function getSampleFieldsGrouped()
    {
        $ir_class_list = ['repertoire'];

        if(config('ireceptor.display_all_ir_fields')) {
            $ir_class_list[] = 'ir_repertoire';
        }

        return static::getFieldsGrouped($ir_class_list);
    }

    public static function getSequenceFieldsGrouped()
    {
        $ir_class_list = ['rearrangement'];

        if(config('ireceptor.display_all_ir_fields')) {
            $ir_class_list[] = 'ir_rearrangement';
        }
        
        return static::getFieldsGrouped($ir_class_list);
    }
}
