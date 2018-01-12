<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Jenssegers\Mongodb\Eloquent\Model;

class QueryLog extends Model
{
    protected $connection = 'mongodb'; // https://github.com/jenssegers/laravel-mongodb
    protected $collection = 'queries';

    protected $guarded = [];
    protected $dates = ['start_time', 'end_time'];

    public static function start_gateway_query($request)
    {
        Log::debug('start gateway query');
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

        if(isset($t['params']['csv'])) {
            $t['file'] = 'csv';
        }

        if (str_contains($url, '/samples')) {
            $type = 'sample';
        } elseif (str_contains($url, '/sequences-quick-search')) {
            $type = 'combined';
        } elseif (str_contains($url, '/sequences')) {
            $type = 'sequence';
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

        $ql->status = $status;
        $ql->message = $message;

        $ql->save();
        Log::debug('end gateway query');
    }

    public static function start_rest_service_query($gw_query_log_id, $rs, $path, $params, $filePath)
    {
        $t = [];

        $now = Carbon::now();
        $now = new \MongoDB\BSON\UTCDateTime($now->timestamp * 1000);

        $t['start_time'] = $now;

        $t['level'] = 'rest_service';

        $url = '/' . $path;
        $t['url'] = $url;

        $t['params'] = $params;

        if(isset($params['output'])){
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

        $t['parent_id'] = $gw_query_log_id;

        $t['rest_service_id'] = $rs->id;
        $t['rest_service_name'] = $rs->name;

        $ql = self::create($t);

        return $ql->id;
    }

    public static function end_rest_service_query($query_log_id, $status = 'done', $message = null)
    {
        $ql = self::find($query_log_id);

        $now = Carbon::now();
        $now_mongo = new \MongoDB\BSON\UTCDateTime($now->timestamp * 1000);
        $ql->end_time = $now_mongo;

        $start_time = Carbon::instance($ql->start_time);
        $duration = $start_time->diffInSeconds($now);
        $ql->duration = $duration;

        $ql->status = $status;
        $ql->message = $message;

        $ql->save();
    }

    public static function find_gateway_queries($all)
    {
        if ($all) {
            $l = static::where('level', '=', 'gateway')->orderBy('start_time', 'desc')->get();
        } else {
            $l = static::where('level', '=', 'gateway')->where('start_time', '>', new \DateTime('-7 days'))->orderBy('start_time', 'desc')->get();
        }

        return $l;
    }

    public static function find_node_queries($id)
    {
        $l = static::where('parent_id', '=', $id)->orderBy('_id', 'desc')->get();

        return $l;
    }
}
