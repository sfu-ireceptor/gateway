<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AlterUserTableAddStatsNotificationDismissedField extends Migration
{
    public function up()
    {
        Schema::table('user', function ($table) {
            $table->boolean('stats_notification_dismissed')->default(false);
        });
    }

    public function down()
    {
        Schema::table('user', function ($table) {
            $table->dropColumn('stats_notification_dismissed');
        });
    }
}
