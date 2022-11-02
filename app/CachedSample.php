<?php

namespace App;

use Illuminate\Support\Str;
use Jenssegers\Mongodb\Eloquent\Model;

class CachedSample extends Model
{
    protected $connection = 'mongodb'; // https://github.com/jenssegers/laravel-mongodb
    protected $collection = 'samples';
    protected $guarded = [];

    // cache samples from REST services
    public static function cache()
    {
        // get data
        $sample_data = Sample::find([], 'titi');  // use Jérôme's username (titi) for now
        $sample_list = $sample_data['items'];

        // delete any previously cached data
        self::truncate();

        // cache data
        foreach ($sample_list as $s) {
            self::create(json_decode(json_encode($s), true));
        }

        return count($sample_list);
    }

    // return cached samples
    public static function cached()
    {
        return self::all();
    }

    // return metadata by querying cached samples
    public static function metadata()
    {
        $t = [];

        // Distinct values for simple sample fields
        $fields = ['template_class', 'ethnicity', 'sex', 'pcr_target_locus'];
        foreach ($fields as $field) {
            $t[$field] = self::distinctValues($field);
        }

        // Distinct values for ontology fields
        $ontology_fields = FieldName::getOntologyFields();
        foreach ($ontology_fields as $field) {
            $t[$field] = self::distinctOntologyValuesGrouped($field);
        }

        // distinct values for combined sample fields (ex: project_id/project_name)
        $t['study_type_ontology_list'] = self::distinctValuesGrouped(['study_type_id', 'study_type']);
        $t['lab_list'] = self::distinctValuesGrouped(['ir_lab_id', 'lab_name']);
        $t['project_list'] = self::distinctValuesGrouped(['ir_project_id', 'study_title']);

        // list of REST services
        $t['rest_service_list'] = RestService::findEnabled(['id', 'name', 'rest_service_group_code'])->toArray();

        // stats
        $t['total_repositories'] = count(self::distinctValuesGrouped(['rest_service_id']));
        $t['total_labs'] = count(self::distinctValuesGrouped(['rest_service_id', 'lab_name']));
        $t['total_projects'] = count(self::distinctValuesGrouped(['rest_service_id', 'study_title']));
        $t['total_samples'] = self::count();
        $t['total_sequences'] = self::sum('ir_sequence_count');

        return $t;
    }

    public static function distinctValues($fieldName)
    {
        $l = self::whereNotNull($fieldName)->distinct($fieldName)->get();
        $l = $l->toArray();

        // replace each array item (a one-item array) by the value directly
        // Ex: [0]=>[[0]=> "Unknown"] is replaced by [0]=> "Unknown"
        $t = [];
        foreach ($l as $lt) {
            if (! empty(trim($lt[0]))) {
                $val = trim($lt[0]);
                if (! in_array($val, $t)) {
                    $t[] = $val;
                }
            }
        }

        return $t;
    }

    public static function distinctValuesGrouped($fields)
    {
        $l = self::groupBy($fields);

        // exclude null values
        foreach ($fields as $fieldName) {
            $l = $l->whereNotNull($fieldName);
        }

        // do query
        $l = $l->get();
        $l = $l->toArray();

        // remove useless '_id' key
        foreach ($l as $k => $v) {
            unset($v['_id']);
            $l[$k] = $v;
        }

        return $l;
    }

    public static function distinctOntologyValuesGrouped($field)
    {
        // We are passed in the base field. Ontology fields have
        // the label in the base field and the ID in the base field
        // with an _id on the end.
        $id_field = $field;
        $label_field = Str::beforeLast($field, '_id');

        // Build a query, group by the ontology id_field, no nulls
        $l = self::groupBy([$id_field]);
        $l = $l->whereNotNull($id_field);
        $l = $l->select([$id_field, $label_field]);

        // do query
        $l = $l->get();
        $l = $l->toArray();

        // We want to restructure the ontology metadata fields
        foreach ($l as $k => $v) {
            // Add the field, ID, and label to the metadata
            $v['field'] = $label_field;
            $v['id'] = $v[$id_field];
            $v['label'] = $v[$label_field];
            // remove useless '_id' key and the original fields
            unset($v['_id']);
            unset($v[$id_field]);
            unset($v[$label_field]);
            // Store the new info in the array.
            $l[$k] = $v;
        }

        return $l;
    }
}
