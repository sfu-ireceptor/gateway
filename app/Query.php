<?php

namespace App;

use Facades\App\FieldName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Query extends Model
{
    protected $table = 'query';
    protected $fillable = ['params', 'duration', 'page'];

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

        // If there are parameters, then process them
        $s = '';
        $parameter_count = 0;
        foreach ($params as $k => $v) {
            // If the key indcates a repertoire field, then skip.
            if (strpos($k, 'ir_project_sample_id_list_') !== false) {
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
        // If nothing left, then say None
        if ($parameter_count == 0) {
            $s .= 'None' . "\n";
        }

        return $s;
    }
}
