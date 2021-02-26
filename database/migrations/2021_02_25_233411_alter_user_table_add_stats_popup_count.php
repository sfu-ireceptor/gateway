<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterUserTableAddStatsPopupCount extends Migration
{
    public function up()
    {
        // drop stats_notification_dismissed
        Schema::table('user', function (Blueprint $table) {
            $table->dropColumn('stats_notification_dismissed');
        });

        Schema::table('user', function ($table) {
            $table->integer('stats_popup_count')->default(0);
        });
    }

    public function down()
    {
        Schema::table('user', function ($table) {
            $table->dropColumn('stats_popup_count');
        });

        // just so it's a real rollback
        Schema::table('user', function ($table) {
            $table->boolean('stats_notification_dismissed')->default(false);
        });
    }
}
