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
        if (Schema::hasColumn('invoices', 'device_id')) {
            if (DB::getDriverName() === 'sqlite') {
                Schema::table('invoices', function (Blueprint $table): void {
                    $table->unsignedBigInteger('device_id')->nullable()->change();
                });
            } else {
                DB::statement('ALTER TABLE `invoices` MODIFY `device_id` BIGINT UNSIGNED NULL');
            }
        }

        if (! Schema::hasColumn('invoices', 'invoice_type')) {
            Schema::table('invoices', function (Blueprint $table): void {
                $table->string('invoice_type', 20)
                    ->default('device_sale')
                    ->after('invoice_no')
                    ->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('invoices', 'invoice_type')) {
            Schema::table('invoices', function (Blueprint $table): void {
                $table->dropIndex(['invoice_type']);
                $table->dropColumn('invoice_type');
            });
        }
    }
};
