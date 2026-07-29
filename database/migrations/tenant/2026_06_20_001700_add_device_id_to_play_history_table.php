<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('play_history', function (Blueprint $table): void {
            $table->foreignId('device_id')->nullable()->after('id')->constrained('devices')->nullOnDelete();
            $table->index(['device_id', 'trx_date']);
        });

        // Backfill device_id from existing device_name values for old rows.
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('UPDATE play_history SET device_id = (SELECT id FROM devices WHERE devices.name = play_history.device_name) WHERE device_id IS NULL AND device_name IS NOT NULL');
        } else {
            DB::statement('UPDATE play_history ph JOIN devices d ON d.name = ph.device_name SET ph.device_id = d.id WHERE ph.device_id IS NULL AND ph.device_name IS NOT NULL');
        }
    }

    public function down(): void
    {
        Schema::table('play_history', function (Blueprint $table): void {
            $table->dropIndex('play_history_device_id_trx_date_index');
            $table->dropConstrainedForeignId('device_id');
        });
    }
};
