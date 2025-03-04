<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class FieldName extends Model
{
    protected $table = 'field_name';
    protected $guarded = [];

    // convert field names for 1 array
    public static function convert($data, $from, $to, $api_version = null)
    {
        $api_version = $api_version ?? config('ireceptor.default_api_version');
        $mapping = self::all([$from, $to, 'api_version'])->where('api_version', $api_version)->toArray();

        return convert_array_keys($data, $mapping, $from, $to);
    }

    // convert field names for a list of arrays
    public static function convertList($data, $from, $to, $ir_class = '', $api_version = null)
    {
        $api_version = $api_version ?? config('ireceptor.default_api_version');
        $mapping = self::all([$from, $to, 'api_version', 'ir_class'])->where('api_version', $api_version)->toArray();

        return convert_arrays_keys($data, $mapping, $from, $to, $ir_class);
    }

    // convert field names for a list of objects
    public static function convertObjectList($data, $from, $to, $ir_class = '', $api_version = null)
    {
        $api_version = $api_version ?? config('ireceptor.default_api_version');
        $mapping = self::all([$from, $to, 'api_version', 'ir_class'])->where('api_version', $api_version)->toArray();

        $array_list = convert_arrays_keys($data, $mapping, $from, $to, $ir_class);

        if (config('ireceptor.display_all_ir_fields')) {
            $array_list = convert_arrays_keys($array_list, $mapping, $from, $to, 'IR_' . $ir_class);
        }

        $object_list = [];
        foreach ($array_list as $a) {
            $object_list[] = (object) $a;
        }

        return $object_list;
    }

    // return field array for a given field name
    public static function getField($field_name, $column = 'ir_id', $api_version = null)
    {
        $api_version = $api_version ?? config('ireceptor.default_api_version');

        $field = static::where($column, $field_name)->where('api_version', $api_version)->first();
        if ($field != null) {
            $field = $field->toArray();
        }

        return $field;
    }

    // return field type for a given field name
    public static function getFieldType($field_id, $column = 'ir_id', $api_version = null)
    {
        $field = static::getField($field_id, $column, $api_version);

        $field_type = null;
        if ($field != null) {
            $field_type = $field['airr_type'];
        }

        return $field_type;
    }

    public static function getSampleFields($api_version = null)
    {
        $api_version = $api_version ?? config('ireceptor.default_api_version');

        $ir_class_list = ['Repertoire'];

        if (config('ireceptor.display_all_ir_fields')) {
            $ir_class_list[] = 'IR_Repertoire';
        }

        $l = static::whereIn('ir_class', $ir_class_list)->where('api_version', $api_version)->orderBy('default_order', 'asc')->get()->toArray();

        return $l;
    }

    public static function getSequenceFields($api_version = null)
    {
        $api_version = $api_version ?? config('ireceptor.default_api_version');

        $ir_class_list = ['Rearrangement'];

        if (config('ireceptor.display_all_ir_fields')) {
            $ir_class_list[] = 'IR_Rearrangement';
        }

        $l = static::whereIn('ir_class', $ir_class_list)->where('api_version', $api_version)->orderBy('default_order', 'asc')->get()->toArray();

        return $l;
    }

    public static function getCloneFields($api_version = null)
    {
        $api_version = $api_version ?? config('ireceptor.default_api_version');

        $ir_class_list = ['Clone'];

        if (config('ireceptor.display_all_ir_fields')) {
            $ir_class_list[] = 'IR_Clone';
        }

        $l = static::whereIn('ir_class', $ir_class_list)->where('api_version', $api_version)->orderBy('default_order', 'asc')->get()->toArray();

        return $l;
    }

    public static function getCellFields($api_version = null)
    {
        $api_version = $api_version ?? config('ireceptor.default_api_version');

        $ir_class_list = ['Cell'];

        if (config('ireceptor.display_all_ir_fields')) {
            $ir_class_list[] = 'IR_Cell';
        }

        $l = static::whereIn('ir_class', $ir_class_list)->where('api_version', $api_version)->orderBy('default_order', 'asc')->get()->toArray();

        return $l;
    }

    public static function getGroups()
    {
        $l = [];

        $l['Repertoire'] = 'Repertoire';
        $l['Study'] = 'Study';
        $l['Subject'] = 'Subject';
        $l['Sample'] = 'Sample';

        $l['Diagnosis'] = 'Diagnosis';
        $l['CellProcessing'] = 'Cell Processing';
        $l['NucleicAcidProcessing'] = 'Nucleic Acid Processing';
        $l['SequencingRun'] = 'Sequencing Run';
        $l['DataProcessing'] = 'Data Processing';
        $l['SampleProcessing'] = 'Sample Processing';
        $l['RawSequenceData'] = 'Raw Sequence Data';
        $l['SequencingData'] = 'Sequencing Data';
        $l['PCRTarget'] = 'PCR Target';

        $l['Genotype'] = 'Receptor Genotype';
        $l['GenotypeSet'] = 'Receptor Genotype Set';
        $l['DeletedGene'] = 'Receptor Genotype Deleted Gene';
        $l['DocumentedAllele'] = 'Receptor Genotype Documented Allele';
        $l['UndocumentedAllele'] = 'Receptor Genotype Undocumented Allele';

        $l['MHCGenotype'] = 'MHC Genotype';
        $l['MHCGenotypeSet'] = 'MHC Genotype Set';
        $l['MHCAllele'] = 'MHC Allele';

        $l['Rearrangement'] = 'Rearrangement';

        $l['Clone'] = 'Clone';
        $l['Cell'] = 'Cell';

        $l['IR_Repertoire'] = 'iReceptor Repertoire';
        $l['ir_metadata'] = 'iReceptor Metadata';
        $l['IR_Parameter'] = 'iReceptor Parameter';
        $l['IR_API'] = 'iReceptor API';
        $l['IR_Curator'] = 'iReceptor Curator';
        $l['rearrangement'] = 'Rearrangement';
        $l['ir_rearrangement'] = 'iReceptor Rearrangement';
        $l['IR_RearrangementDB'] = 'iReceptor Rearrangement (database)';
        $l['IR_RearrangementPair'] = 'iReceptor Rearrangement (pair)';
        $l['IR_CloneDB'] = 'iReceptor Clone (database)';
        $l['IR_CellDB'] = 'iReceptor Cell (database)';

        $l['other'] = 'Other';

        return $l;
    }

    public static function getFieldsGrouped($ir_class_list)
    {
        $api_version = config('ireceptor.default_api_version');

        $l = static::whereIn('ir_class', $ir_class_list)->orderBy('ir_subclass', 'asc')->where('api_version', $api_version)->orderBy('ir_short', 'asc')->get()->toArray();
        $groups = static::getGroups();

        $gl = [];
        foreach ($groups as $group_key => $group_name) {
            foreach ($l as $t) {
                // if ir_subclass is not known, log warning and override it to 'other'
                if (! isset($groups[$t['ir_subclass']])) {
                    if ($t['ir_subclass'] == '') {
                        Log::warning($t['ir_id'] . ' does not have an ir_subclass value');
                    } else {
                        Log::warning($t['ir_subclass'] . ' ir_subclass needs to be defined as a group in ' . static::class);
                    }
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
        $ir_class_list = ['Repertoire'];

        if (config('ireceptor.display_all_ir_fields')) {
            $ir_class_list[] = 'IR_Repertoire';
        }

        return static::getFieldsGrouped($ir_class_list);
    }

    public static function getSequenceFieldsGrouped()
    {
        $ir_class_list = ['Rearrangement'];

        if (config('ireceptor.display_all_ir_fields')) {
            $ir_class_list[] = 'IR_Rearrangement';
        }

        return static::getFieldsGrouped($ir_class_list);
    }

    public static function getCloneFieldsGrouped()
    {
        $ir_class_list = ['Clone'];

        if (config('ireceptor.display_all_ir_fields')) {
            $ir_class_list[] = 'IR_Clone';
        }

        return static::getFieldsGrouped($ir_class_list);
    }

    public static function getCellFieldsGrouped()
    {
        $ir_class_list = ['Cell'];

        if (config('ireceptor.display_all_ir_fields')) {
            $ir_class_list[] = 'IR_Cell';
        }

        return static::getFieldsGrouped($ir_class_list);
    }

    public static function getAPIVersions()
    {
        $api_version_list = [];

        $l = self::groupBy('api_version')->get('api_version')->toArray();
        foreach ($l as $t) {
            $api_version_list[] = $t['api_version'];
        }

        return $api_version_list;
    }

    public static function getOntologyFields()
    {
        return ['tissue_id', 'organism_id', 'study_type_id', 'age_unit_id', 'disease_diagnosis_id', 'cell_subset_id', 'collection_time_point_relative_unit_id', 'template_amount_unit_id'];
    }
}
