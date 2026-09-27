<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('system_configs')->insertOrIgnore([
            'key' => 'sys_iot_connection_type',
            'value' => 'bridge',
            'group' => 'system',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('system_configs')
            ->where('key', 'sys_iot_connection_type')
            ->where('value', 'bridge')
            ->delete();
    }
};
