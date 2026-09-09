<?php

namespace App\Console\Commands;

use App\Jobs\DeleteExpiredTenantDataJob;
use App\Models\Tenant;
use Illuminate\Console\Command;

class CleanupExpiredTenants extends Command
{
    protected $signature = 'tenants:cleanup-expired {--dry-run : List expired tenants without deleting them}';

    protected $description = 'Delete data for tenants whose subscription has expired 3 days ago';

    public function handle(): int
    {
        if ($this->option('dry-run')) {
            $tenants = Tenant::query()
                ->where('ended_at', '<=', now()->subDays((int) config('tenancy.expiration_grace_days', 3)))
                ->get()
                ->reject(fn (Tenant $tenant): bool => $tenant->getAttribute('provisioning_status') === 'provisioning');

            $this->table(['Tenant ID', 'Expired at'], $tenants->map(fn (Tenant $tenant): array => [
                $tenant->getTenantKey(), $tenant->ended_at->toDateTimeString(),
            ])->all());

            return self::SUCCESS;
        }

        $this->info('Starting cleanup of expired tenant data...');

        DeleteExpiredTenantDataJob::dispatch();

        $this->info('Cleanup job dispatched successfully.');

        return self::SUCCESS;
    }
}
