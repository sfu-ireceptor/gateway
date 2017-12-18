<?php

namespace App;

use Carbon\Carbon;
use Jenssegers\Mongodb\Eloquent\Model;

class QueryLog extends Model
{
    protected $connection = 'mongodb'; // https://github.com/jenssegers/laravel-mongodb
    protected $collection = 'queries';
    protected $guarded = [];

    public static function start_gateway_query($request)
    {
        $t = [];

        $now = Carbon::now();
        $now = new \MongoDB\BSON\UTCDateTime($now->timestamp * 1000);

        $t['start_time'] = $now;

        $t['level'] = 'gateway';

        $url = '/' . $request->path();
        $t['url'] = $url;

        $params = $request->all();
        $t['params'] = $request->query();

        if (str_contains($url, '/samples')) {
            $type = 'sample';
        } elseif (str_contains($url, '/sequences')) {
            $type = 'sequence';
        } else {
            $type = 'unknown';
        }
        $t['type'] = $type;

        $t['status'] = 'running';

        $ql = self::create($t);

        return $ql->id;
    }

    public static function end_gateway_query($query_log_id, $status = 'done', $message = null)
    {
        $ql = self::find($query_log_id);

        $now = Carbon::now();
        $now_mongo = new \MongoDB\BSON\UTCDateTime($now->timestamp * 1000);
        $ql->end_time = $now_mongo;

        $start_time = Carbon::instance($ql->start_time->toDateTime());
        $duration = $start_time->diffInSeconds($now);
        $ql->duration = $duration;

        $ql->status = $status;
        $ql->message = $message;

        $ql->save();
    }
}
