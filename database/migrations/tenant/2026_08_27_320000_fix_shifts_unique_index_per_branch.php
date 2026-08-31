<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('shifts')) {
            Schema::table('shifts', function (Blueprint $table): void {
                // Drop global unique constraint on shift_no if exists
                try {
                    $table->dropUnique('shifts_shift_no_unique');
                } catch (Throwable) {
                    // Unique index might have been dropped or named differently
                }

                // Add compound unique constraint per branch
                try {
                    $table->unique(['branch_id', 'shift_no'], 'shifts_branch_id_shift_no_unique');
                } catch (Throwable) {
                    // Index already exists
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('shifts')) {
            Schema::table('shifts', function (Blueprint $table): void {
                try {
                    $table->dropUnique('shifts_branch_id_shift_no_unique');
                } catch (Throwable) {
                    // Index doesn't exist
                }

                try {
                    $table->unique('shift_no', 'shifts_shift_no_unique');
                } catch (Throwable) {
                    // Index already exists
                }
            });
        }
    }
};
