<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;
use Carbon\Carbon;

class QueryLog extends Model
{
    protected $connection = 'mongodb'; // https://github.com/jenssegers/laravel-mongodb
    protected $collection = 'queries';
    protected $guarded = [];

    // cache samples from REST services
    public static function start_gateway_query($request)
    {
        $t = [];

        $now = new Carbon('now');
        $t['start_time'] = $now;
        
        $t['level'] = 'gateway';
        
        $url = '/' . $request->path();
        $t['url'] = $url;
        
        $params = $request->all();
        $t['params'] = $params;

		if(str_contains($url, '/samples'))
		{
			$type = 'sample';
		}
		else if (str_contains($url, '/sequences'))
		{
			$type = 'sequence';
		}
		else
		{
			$type = 'unknown';
		}
        $t['type'] = $type;

        $t['status'] = 'running';
        $t['message'] = '';

        $ql = self::create($t);
        return $ql->id;
    }

    public static function end_gateway_query($query_log_id, $status)
    {
    	$ql = self::find($query_log_id);

        $now = new Carbon('now');
    	$ql->end_time = $now;

    	$ql->status = $status;

    	// duration

    	$ql->save();
    }

}
