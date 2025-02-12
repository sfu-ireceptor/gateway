<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Jenssegers\Mongodb\Eloquent\Model;

class QueryLog extends Model
{
    protected $connection = 'mongodb'; // https://github.com/jenssegers/laravel-mongodb
    protected $collection = 'queries';

    protected $guarded = [];
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public static function get_query_log_id()
    {
        $query_log_id = request()->get('query_log_id');
        if ($query_log_id == null) {
            $query_log_id = session()->get('query_log_id');
        }

        return $query_log_id;
    }

    public static function start_job($page_url, $filters, $nb_sequences, $username)
    {
        Log::debug('#### QueryLog::start_job');
        $t = [];

        $now = Carbon::now();
        $now = new \MongoDB\BSON\UTCDateTime($now->timestamp * 1000);

        $t['start_time'] = $now;

        $t['level'] = 'job';

        $t['url'] = $page_url;
        $t['params'] = $filters;
        $t['result_size'] = $nb_sequences;

        $t['type'] = 'job';

        $t['status'] = 'running';
        $t['username'] = $username;

        $ql = self::create($t);

        session()->put('query_log_id', $ql->id);

        return $ql->id;
    }

    public static function end_job($query_log_id, $status = 'done', $message = null)
    {
        $ql = self::find($query_log_id);

        $now = Carbon::now();
        $now_mongo = new \MongoDB\BSON\UTCDateTime($now->timestamp * 1000);
        $ql->end_time = $now_mongo;

        $start_time = Carbon::instance($ql->start_time);
        $duration = $start_time->diffInSeconds($now);
        $ql->duration = $duration;

        // update query status only if there is a job error (more important than service error)
        // or no service error (in which case the status will be set to 'done')
        if ($status != 'done' || $ql->status == 'running') {
            $ql->status = $status;
            $ql->message = $message;
        }

        $ql->save();
        Log::debug('#### QueryLog::end_job');
    }

    public static function start_gateway_query($request)
    {
        Log::debug('#### QueryLog::start_gateway_query');
        $t = [];

        $now = Carbon::now();
        $now = new \MongoDB\BSON\UTCDateTime($now->timestamp * 1000);

        $t['start_time'] = $now;

        $t['level'] = 'gateway';

        $url = $request->fullUrl();
        $t['url'] = $url;

        $t['params'] = $request->query();
        if (isset($t['params']['query_id'])) {
            $params = Query::getParams($t['params']['query_id']);

            // remove null values.
            foreach ($params as $k => $v) {
                if ($v === null) {
                    unset($params[$k]);
                }
            }
            $t['params'] = $params;
        }

        if (str_contains($url, '/samples/json')) {
            $type = 'sample';
            $t['file'] = 'json';
        } elseif (str_contains($url, '/samples/tsv')) {
            $type = 'sample';
            $t['file'] = 'tsv';
        } elseif (str_contains($url, '/samples/clone')) {
            $type = 'sample-clone';
        } elseif (str_contains($url, '/samples/cell')) {
            $type = 'sample-cell';
        } elseif (str_contains($url, '/samples')) {
            $type = 'sample';
        } elseif (str_contains($url, '/sequences-quick-search')) {
            $type = 'combined';
        } elseif (str_contains($url, '/sequences-download')) {
            $type = 'sequence';
        } elseif (str_contains($url, '/sequences')) {
            $type = 'sequence';
        } elseif (str_contains($url, '/clones')) {
            $type = 'clone';
        } elseif (str_contains($url, '/clones-download')) {
            $type = 'clone';
        } elseif (str_contains($url, '/cells')) {
            $type = 'cell';
        } elseif (str_contains($url, '/cells-download')) {
            $type = 'cell';
        } else {
            $type = 'unknown';
        }
        $t['type'] = $type;

        $t['status'] = 'running';
        $t['username'] = auth()->user()->username;

        $ql = self::create($t);

        return $ql->id;
    }

    public static function end_gateway_query($query_log_id, $status = 'done', $message = null)
    {
        $ql = self::find($query_log_id);

        $now = Carbon::now();
        $now_mongo = new \MongoDB\BSON\UTCDateTime($now->timestamp * 1000);
        $ql->end_time = $now_mongo;

        $start_time = Carbon::instance($ql->start_time);
        $duration = $start_time->diffInSeconds($now);
        $ql->duration = $duration;

        // update gw query status only if there is a gw error (more important than service error)
        // or no service error (in which case the status will be set to 'done')
        if ($status != 'done' || $ql->status == 'running') {
            $ql->status = $status;
            $ql->message = $message;
        }

        $ql->save();
        Log::debug('#### QueryLog::end_gateway_query');
    }

    public static function set_gateway_query_status($query_log_id, $status = 'done', $message = null)
    {
        if ($query_log_id != null) {
            $ql = self::find($query_log_id);

            $ql->status = $status;
            $ql->message = $message;

            $ql->save();
            Log::debug('set gateway query to ' . $status);
        }
    }

    public static function get_gateway_query_status($query_log_id)
    {
        $status = '';
        if ($query_log_id != null) {
            $ql = self::find($query_log_id);
            $status = $ql->status;
        }

        return $status;
    }

    public static function get_gateway_query_message($query_log_id)
    {
        $message = '';
        if ($query_log_id != null) {
            $ql = self::find($query_log_id);
            $message = $ql->message;
        }

        return $message;
    }

    public static function start_rest_service_query($rest_service_id, $rest_service_name, $path, $params, $filePath)
    {
        $t = [];

        $now = Carbon::now();
        $now = new \MongoDB\BSON\UTCDateTime($now->timestamp * 1000);

        $t['start_time'] = $now;

        $t['level'] = 'rest_service';

        $url = $path;
        $t['url'] = $url;

        $t['params'] = $params;

        if (isset($params['output'])) {
            $t['file'] = $params['output'];
        }

        if (str_contains($url, '/samples')) {
            $type = 'sample';
        } elseif (str_contains($url, '/sequences')) {
            $type = 'sequence';
        } else {
            $type = 'unknown';
        }
        $t['type'] = $type;

        $t['status'] = 'running';

        $t['parent_id'] = self::get_query_log_id();

        $t['rest_service_id'] = $rest_service_id;
        $t['rest_service_name'] = $rest_service_name;

        $ql = self::create($t);

        Log::debug('QueryLog::start_rest_service_query - Start node query ' . $ql->id . ' to ' . $path . ' with POST params: ' . Str::limit(json_encode($params), 250));

        return $ql->id;
    }

    public static function end_rest_service_query($query_log_id, $result_size = '', $status = 'done', $message = null)
    {
        Log::debug('QueryLog::end_rest_service_query - Finished node query: ' . $query_log_id . ' with status: ' . $status);
        if ($message != null) {
            Log::debug($message);
        }

        $ql = self::find($query_log_id);

        $now = Carbon::now();
        $now_mongo = new \MongoDB\BSON\UTCDateTime($now->timestamp * 1000);
        $ql->end_time = $now_mongo;

        $start_time = Carbon::instance($ql->start_time);
        $duration = $start_time->diffInSeconds($now);
        $ql->duration = $duration;

        $ql->status = $status;
        $ql->message = $message;

        $ql->result_size = $result_size;

        $ql->save();
    }

    public static function update_rest_service_query($query_log_id, $result_size = '', $status = 'done', $message = null)
    {
        $ql = self::find($query_log_id);

        $ql->status = $status;
        $ql->message = $message;

        $ql->result_size = $result_size;

        $ql->save();
    }

    public static function find_gateway_queries($nb_months = null)
    {
        if ($nb_months == null) {
            $l = static::where('level', '=', 'gateway')->where('start_time', '>', new \DateTime('-7 days'))->orderBy('_id', 'desc')->get();
        } else {
            // $l = static::where('level', '=', 'gateway')->orderBy('start_time', 'desc')->get();
            $l = static::where('level', '=', 'gateway')->where('start_time', '>', new \DateTime('-' . $nb_months . ' months'))->orderBy('_id', 'desc')->get();
        }

        return $l;
    }

    public static function find_node_queries($id)
    {
        $l = static::where('parent_id', '=', $id)->orderBy('_id', 'desc')->get();

        return $l;
    }
}
