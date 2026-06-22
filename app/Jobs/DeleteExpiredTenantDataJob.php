<?php

namespace App\Jobs;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteExpiredTenantDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // ابحث عن Tenants التي انتهت اشتراكاتها من 3 أيام فما فوق
        $expiredTenants = Tenant::where('ended_at', '<=', now()->subDays(3))->get();

        foreach ($expiredTenants as $tenant) {
            try {
                tenancy()->initialize($tenant);

                // حذف جميع بيانات الـ tenant من قاعدة البيانات الخاصة به
                $this->deleteAllTenantData();

                // حذف الـ tenant من الـ central database
                $tenant->forceDelete();

                // تسجيل عملية الحذف
                Log::info("Tenant {$tenant->id} data deleted successfully after expiry period");
            } catch (\Exception $e) {
                Log::error("Failed to delete tenant {$tenant->id}: {$e->getMessage()}");
            } finally {
                tenancy()->end();
            }
        }
    }

    private function deleteAllTenantData(): void
    {
        $tables = DB::connection(tenancy()->connection())->getSchemaBuilder()->getTables();

        foreach ($tables as $table) {
            // تجنب حذف جداول النظام
            if (in_array($table['name'], ['migrations', 'failed_jobs'])) {
                continue;
            }

            try {
                DB::connection(tenancy()->connection())->table($table['name'])->truncate();
            } catch (\Exception $e) {
                Log::warning("Could not truncate table {$table['name']}: {$e->getMessage()}");
            }
        }
    }
}
