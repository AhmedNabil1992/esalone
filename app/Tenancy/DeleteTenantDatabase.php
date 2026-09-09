<?php

declare(strict_types=1);

namespace App\Tenancy;

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

        $database = $event->tenant->database();

        if ($database->manager()->databaseExists($database->getName())) {
            event(new DeletingDatabase($event->tenant));

            if (! $database->manager()->deleteDatabase($event->tenant)) {
                throw new RuntimeException('Failed to delete tenant database.');
            }

            event(new DatabaseDeleted($event->tenant));
        }
    }
}
