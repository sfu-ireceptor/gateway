<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;
use App\QueryLog;

class LogQuery
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $query_log_id = QueryLog::start_gateway_query($request);

        $response = $next($request);        

        if(empty($response->exception))  {
            QueryLog::end_gateway_query($query_log_id);
        }    
        else {
            QueryLog::end_gateway_query($query_log_id, 'error', $response->exception->getMessage());        
        }

        return $response;
    }
}
