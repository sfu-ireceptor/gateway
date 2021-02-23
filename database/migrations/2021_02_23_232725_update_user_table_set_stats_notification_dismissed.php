<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateUserTableSetStatsNotificationDismissed extends Migration
{
    public function up()
    {
        $affected = DB::update('update user set stats_notification_dismissed = ?', [0]);
    }

    public function down()
    {
    }
}
