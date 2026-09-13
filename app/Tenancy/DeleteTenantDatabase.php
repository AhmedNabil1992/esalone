<?php

declare(strict_types=1);

namespace App\Tenancy;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Stancl\Tenancy\Events\DatabaseDeleted;
use Stancl\Tenancy\Events\DeletingDatabase;
use Stancl\Tenancy\Events\DeletingTenant;

class DeleteTenantDatabase
{
    public function handle(DeletingTenant $event): void
    {
        if (tenancy()->initialized) {
            throw new RuntimeException('Tenant deletion must run in the central context.');
        }

        $tenant = $event->tenant;
        $tenantId = (string) $tenant->getTenantKey();
        $suffix = (string) config('tenancy.filesystem.suffix_base', 'tenant');
        if (! preg_match('/\A[a-zA-Z0-9_-]+\z/', $suffix.$tenantId)) {
            throw new RuntimeException('Invalid tenant storage directory.');
        }
        $storageRoot = realpath(storage_path());
        $tenantStorage = storage_path($suffix.$tenantId);
        if (is_link($tenantStorage) || (file_exists($tenantStorage)
            && (! is_dir($tenantStorage) || dirname(realpath($tenantStorage)) !== $storageRoot))) {
            throw new RuntimeException('Tenant storage must remain inside the application storage directory.');
        }

        $tenant->setAttribute('deletion_started_at', now()->toIso8601String());
        $tenant->save();
        $database = $tenant->database();

        if ($database->manager()->databaseExists($database->getName())) {
            event(new DeletingDatabase($event->tenant));

            if (! $database->manager()->deleteDatabase($event->tenant)) {
                throw new RuntimeException('Failed to delete tenant database.');
            }

            event(new DatabaseDeleted($event->tenant));
        }

        if (is_dir($tenantStorage) && ! File::deleteDirectory($tenantStorage)) {
            throw new RuntimeException('Failed to delete tenant files.');
        }

        Cache::store()->forget(config('permission.cache.key').'.tenant.'.$tenantId);
        if (Cache::store()->supportsTags()) {
            Cache::store()->tags(config('tenancy.cache.tag_base', 'tenant').$tenantId)->flush();
        }
    }
}
