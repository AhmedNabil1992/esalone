<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('play_history') && Schema::hasTable('invoices')) {
            DB::table('play_history')
                ->whereNotNull('invoice_no')
                ->select(['id', 'invoice_no'])
                ->orderBy('id')
                ->chunkById(500, function ($rows): void {
                    foreach ($rows as $row) {
                        $shiftId = DB::table('invoices')->where('invoice_no', $row->invoice_no)->value('shift_id');

                        if ($shiftId !== null) {
                            DB::table('play_history')->where('id', $row->id)->update(['shift_id' => $shiftId]);
                        }
                    }
                });
        }
    }

    public function down(): void
    {
        // No rollback needed
    }
};
