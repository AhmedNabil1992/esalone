<?php

namespace App\Console\Commands;

use App\Jobs\DeleteExpiredTenantDataJob;
use Illuminate\Console\Command;

class CleanupExpiredTenants extends Command
{
    protected $signature = 'tenants:cleanup-expired';

    protected $description = 'Delete data for tenants whose subscription has expired 3 days ago';

    public function handle()
    {
        $this->info('Starting cleanup of expired tenant data...');

        DeleteExpiredTenantDataJob::dispatch();

        $this->info('Cleanup job dispatched successfully.');
    }
}
