<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Auth\Events\Login;

class UpdateLastLogin
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \Illuminate\Auth\Events\Login  $event
     * @return void
     */
    public function handle(Login $event)
    {
        try {
            $user = $event->user;
            $now = Carbon::now()->toDateTimeString();
            $user->last_login = $now;
            $user->save();
        } catch (\Throwable $th) {
            report($th);
        }
    }
}
