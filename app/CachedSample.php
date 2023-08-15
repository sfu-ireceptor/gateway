<?php

namespace App;

use Illuminate\Support\Facades\Log;
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
        // delete any previously cached data
        self::truncate();

        // Keep track of number of samples cached.
        $sample_count = 0;
        // get sequnce data
        $sample_data = Sample::find([], 'titi');  // use Jérôme's username (titi) for now
        $sample_list = $sample_data['items'];

        // cache data
        foreach ($sample_list as $s) {
            $s->ir_repertoire_type = 'sequence';
            self::create(json_decode(json_encode($s), true));
        }
        $sample_count += count($sample_list);

        // get clone data
        $sample_data = Sample::find([], 'titi', true, 'clone');
        $sample_list = $sample_data['items'];

        // cache data
        foreach ($sample_list as $s) {
            $s->ir_repertoire_type = 'clone';
            self::create(json_decode(json_encode($s), true));
        }
        $sample_count += count($sample_list);

        // get cell data
        $sample_data = Sample::find([], 'titi', true, 'cell');  // use Jérôme's username (titi) for now
        $sample_list = $sample_data['items'];

        // cache data
        foreach ($sample_list as $s) {
            $s->ir_repertoire_type = 'cell';
            self::create(json_decode(json_encode($s), true));
        }
        $sample_count += count($sample_list);

        return $sample_count;
    }

    // return cached samples
    public static function cached($sample_type = 'sequence')
    {
        return self::where('ir_repertoire_type', '=', $sample_type)->get();
        //return self::all();
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

        // Get the total number of repositories.
        $t['total_repositories'] = count(self::distinctValuesGrouped(['rest_service_id']));
        $t['total_repositories_sequences'] = count(self::distinctValuesFiltered('rest_service_id', 'ir_repertoire_type', 'sequence'));
        $t['total_repositories_clones'] = count(self::distinctValuesFiltered('rest_service_id', 'ir_repertoire_type', 'clone'));
        $t['total_repositories_cells'] = count(self::distinctValuesFiltered('rest_service_id', 'ir_repertoire_type', 'cell'));

        // Get the total number of labs.
        $t['total_labs'] = count(self::distinctValuesGrouped(['rest_service_id', 'lab_name']));

        // Get the study counts for total, sequence, clone, and cell studies.
        $t['total_projects'] = count(self::distinctValuesGrouped(['rest_service_id', 'study_title']));
        $t['total_projects_sequences'] = count(self::distinctValuesFiltered('study_title', 'ir_repertoire_type', 'sequence'));
        $t['total_projects_cells'] = count(self::distinctValuesFiltered('study_title', 'ir_repertoire_type', 'cell'));
        $t['total_projects_clones'] = count(self::distinctValuesFiltered('study_title', 'ir_repertoire_type', 'clone'));

        // Get the repertoire counts for all, sequence, clone, and cell repertoires.
        $t['total_samples'] = self::count();
        $t['total_samples_sequences'] = self::where('ir_repertoire_type', '=', 'sequence')->count();
        $t['total_samples_clones'] = self::where('ir_repertoire_type', '=', 'clone')->count();
        $t['total_samples_cells'] = self::where('ir_repertoire_type', '=', 'cell')->count();

        // Get the total counts for sequences, clones, and cells.
        $t['total_sequences'] = self::sum('ir_sequence_count');
        $t['total_clones'] = self::sum('ir_clone_count');
        $t['total_cells'] = self::sum('ir_cell_count');

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

    public static function distinctValuesFiltered($fieldName, $filterField, $filterValue)
    {
        $l = self::where($filterField, '=', $filterValue)->whereNotNull($fieldName)->distinct($fieldName)->get();
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
        Log::debug('distinctValuesGrouped: l = ' . json_encode($l));

        // exclude null values
        foreach ($fields as $fieldName) {
            $l = $l->whereNotNull($fieldName);
        }
        Log::debug('distinctValuesGrouped: l = ' . json_encode($l));

        // do query
        $l = $l->get();
        $l = $l->toArray();
        Log::debug('distinctValuesGrouped: l = ' . json_encode($l));

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

        // sort by label
        usort($l, function ($a, $b) {
            return strcasecmp($a['label'], $b['label']);
        });

        return $l;
    }
}
