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
        if (Schema::hasColumn('play_history', 'shift_no') && ! Schema::hasColumn('play_history', 'shift_id')) {
            Schema::table('play_history', function (Blueprint $table): void {
                $table->renameColumn('shift_no', 'shift_id');
            });
        }

        if (Schema::hasColumn('market_history', 'shift_no') && ! Schema::hasColumn('market_history', 'shift_id')) {
            Schema::table('market_history', function (Blueprint $table): void {
                $table->renameColumn('shift_no', 'shift_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('play_history', 'shift_id')) {
            Schema::table('play_history', function (Blueprint $table): void {
                $table->renameColumn('shift_id', 'shift_no');
            });
        }

        if (Schema::hasColumn('market_history', 'shift_id')) {
            Schema::table('market_history', function (Blueprint $table): void {
                $table->renameColumn('shift_id', 'shift_no');
            });
        }
    }
};
