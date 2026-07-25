<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('play_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('play_sessions', 'alert_ended_sent')) {
                $table->boolean('alert_ended_sent')->default(false)->after('alert_5min_sent');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('play_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('play_sessions', 'alert_ended_sent')) {
                $table->dropColumn('alert_ended_sent');
            }
        });
    }
};
