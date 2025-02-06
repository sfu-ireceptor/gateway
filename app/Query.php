<?php

namespace App;

use Facades\App\FieldName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Query extends Model
{
    protected $table = 'query';
    protected $fillable = ['params', 'duration', 'page'];

    // Parameteres to queries differ depending on the type of page.
    // They are encoded as a JSON string in the database.
    //
    // The samples page has the following:
    //   - project_id_list
    //   - cols
    //   - sort_column
    //   - sort_order
    //   - open_filter_panel_list
    //   - the fields that are queryable with the key the field and the value the query text
    //
    // The sequence, clone, and cell pages have the following:
    //   - A set of repository keys of the form ir_project_sample_id_list_NN
    //     where NN is the ID of the repository in question. These keys capture
    //     which repositories should be queried. The value of each key is an array
    //     of the repertoire_id's (sample IDs) that should be queried for that
    //     repository.
    //   - cols
    //   - open_filter_panel_list
    //   - sample_query_id: The query_id for the sample query that resulted in
    //     this sequence query
    //   - the fields that are queryable on this page with the key the field and
    //     the value the query text
    public static function saveParams($params, $page)
    {
        $t = [];
        $t['params'] = json_encode($params);
        $t['page'] = $page;

        $q = static::create($t);

        return $q->id;
    }

    public static function getParams($id)
    {
        $q = static::find($id);

        if ($q == null) {
            return [];
        }

        return json_decode($q->params, true);
    }

    public static function sampleParamsSummary($params)
    {
        // Remove the query parameters that are internal.
        unset($params['project_id_list']);
        unset($params['cols']);
        unset($params['sort_order']);
        unset($params['sort_column']);
        unset($params['open_filter_panel_list']);
        unset($params['extra_field']);
        unset($params['page']);

        // If there are parameters, then process them
        $s = '';
        $parameter_count = 0;
        $ontology_fields = FieldName::getOntologyFields();
        Log::debug('sampleParamsSummary: params = ' . json_encode($params));
        Log::debug('sampleParamsSummary: ontology fields = ' . implode($ontology_fields));
        foreach ($params as $k => $v) {
            // If the value is null, it isn't a filter.
            if ($v == null) {
                continue;
            }
            // If it is an array, compile it as a list.
            if (is_array($v)) {
                $v = implode(' or ', $v);
            }
            // use human-friendly filter name
            Log::debug('sampleParamsSummary: key = ' . $k);
            if (in_array($k, $ontology_fields)) {
                // TODO: IR-2878 - We need an ontology lookup here, where we can look up
                // the value for the ID and get back the correct label.
                $s .= __('short.' . $k) . ': ' . $v . "\n";
            } else {
                $s .= __('short.' . $k) . ': ' . $v . "\n";
            }
            $parameter_count++;
        }
        // If nothing left, then say None
        if ($parameter_count == 0) {
            $s .= 'None' . "\n";
        }

        return $s;
    }

    public static function sequenceParamsSummary($params)
    {
        // Remove the query parameters that are internal.
        unset($params['sample_query_id']);
        unset($params['cols']);
        unset($params['open_filter_panel_list']);
        Log::debug('sequenceParamsSummary: params = ' . json_encode($params));

        // If there are parameters, then process them
        $s = '';
        $parameter_count = 0;
        $repertoire_count = 0;
        foreach ($params as $k => $v) {
            // If the key indcates a repertoire field, then skip.
            if (strpos($k, 'ir_project_sample_id_list_') !== false) {
                if (is_array($v) && count($v) == 1) {
                    $repertoire_count = $repertoire_count + 1;
                    $repertoire_id = $v[0];
                }
                continue;
            }
            // If the value is null, it isn't a filter.
            if ($v == null) {
                continue;
            }
            // If it is an array, compile it as a list.
            if (is_array($v)) {
                $v = implode(' or ', $v);
            }
            // use human-friendly filter name
            $s .= __('short.' . $k) . ': ' . $v . "\n";
            $parameter_count++;
        }
        if ($repertoire_count == 1) {
            $s .= __('short.' . 'repertoire_id') . ': ' . $repertoire_id . "\n";
            $parameter_count++;
        }

        // If nothing left, then say None
        if ($parameter_count == 0) {
            $s .= 'None' . "\n";
        }

        return $s;
    }
}
