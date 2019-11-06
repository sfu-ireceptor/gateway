<?php

namespace App;

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

        // distinct values for simple sample fields
        $fields = ['study_type', 'template_class', 'ethnicity', 'tissue', 'sex', 'cell_subset', 'organism'];
        foreach ($fields as $field) {
            $t[$field] = self::distinctValues($field);
        }

        // distinct values for combined sample fields (ex: project_id/project_name)
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
            $t[] = $lt[0];
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
}
