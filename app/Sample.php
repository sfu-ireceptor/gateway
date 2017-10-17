<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;

class Sample extends Model
{
    protected $connection = 'mongodb'; // https://github.com/jenssegers/laravel-mongodb
    protected $collection = 'samples';
    protected $guarded = [];

    public static function metadata()
    {
        $t = [];

        // distinct values for simple sample fields
        $fields = ['case_control_name', 'dna_type', 'subject_ethnicity', 'sample_source_name', 'subject_gender', 'ireceptor_cell_subset_name'];
        foreach ($fields as $field) {
            $t[$field] = self::distinctValues($field);
        }

        // distinct values for combined sample fields (ex: project_id/project_name)
        $t['lab_list'] = self::distinctValuesGrouped(['lab_id', 'lab_name']);
        $t['project_list'] = self::distinctValuesGrouped(['project_id', 'project_name']);

        // list of REST services
        $t['rest_service_list'] = RestService::findEnabled(['id', 'name'])->toArray();

        // stats
        $t['total_repositories'] = RestService::findEnabled()->count();
        $t['total_labs'] = count(self::distinctValuesGrouped(['rest_service_id', 'lab_id']));
        $t['total_projects'] = count(self::distinctValuesGrouped(['rest_service_id', 'project_id']));
        $t['total_samples'] = self::count();
        $t['total_sequences'] = self::sum('sequence_count');

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
