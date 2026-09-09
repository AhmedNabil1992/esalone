<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class DeleteExpiredTenantDataJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public int $uniqueFor = 3600;

    public function __construct()
    {
        $this->onConnection('database');
        $this->onQueue('tenant-provisioning');
    }

    public function handle(): void
    {
        if (tenancy()->initialized) {
            throw new RuntimeException('Expired tenant cleanup must run in the central context.');
        }

        $cutoff = now()->subDays((int) config('tenancy.expiration_grace_days', 3));
        $failed = false;

        Tenant::query()->where('ended_at', '<=', $cutoff)->lazyById(100)->each(function (Tenant $tenant) use ($cutoff, &$failed): void {
            try {
                $tenant = $tenant->fresh();

                if (! $tenant || ! $tenant->ended_at || $tenant->ended_at->isAfter($cutoff)) {
                    return;
                }

                if ($tenant->getAttribute('provisioning_status') === 'provisioning') {
                    return;
                }

                $tenant->delete();
                Log::info('Expired tenant deleted.', ['tenant_id' => $tenant->getTenantKey()]);
            } catch (Throwable $throwable) {
                $failed = true;
                Log::error('Failed to delete expired tenant.', [
                    'tenant_id' => $tenant?->getTenantKey(),
                    'error' => $throwable->getMessage(),
                ]);
            }
        });

        if ($failed) {
            throw new RuntimeException('Some expired tenants could not be deleted; see the application log.');
        }
    }
}
