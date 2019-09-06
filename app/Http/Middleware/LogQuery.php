<?php

namespace App\Http\Middleware;

use Closure;
use App\QueryLog;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;

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
        // do nothing if it's just a test
        if (App::environment() == 'testing') {
            return $next($request);
        }

        $query_log_id = QueryLog::start_gateway_query($request);

        $request->attributes->set('query_log_id', $query_log_id);
        $response = $next($request);

        if (! empty($response->exception)) {
            $error_message = $response->exception->getMessage();
            QueryLog::end_gateway_query($query_log_id, 'error', $error_message);
        } else {
            QueryLog::end_gateway_query($query_log_id);
        }

        $gw_query_status = QueryLog::get_gateway_query_status($query_log_id);

        // if error, send email notification
        if ($gw_query_status != 'done') {
            if (App::environment() == 'production') {
                $username = auth()->user()->username;
                $error_message = QueryLog::get_gateway_query_message($query_log_id);

                $t = [];
                $t['username'] = $username;
                $t['error_message'] = $error_message;
                $t['user_query_admin_page_url'] = config('app.url') . '/admin/queries/' . $query_log_id;

                Mail::send(['text' => 'emails.data_query_error'], $t, function ($message) use ($username) {
                    $message->to(config('ireceptor.email_support'))->subject('Gateway User Query Error for ' . $username);
                });
            }
        }

        return $response;
    }
}
