<?php

use App\Actions\Tenant\ManageSessionOrderAction;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = collect([
            ManageSessionOrderAction::UPDATE_PERMISSION,
            ManageSessionOrderAction::DELETE_PERMISSION,
        ])->map(fn (string $name): Permission => Permission::findOrCreate($name, 'web'));

        Role::query()
            ->where('name', 'super_admin')
            ->where('guard_name', 'web')
            ->first()
            ?->givePermissionTo($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::query()
            ->whereIn('name', [
                ManageSessionOrderAction::UPDATE_PERMISSION,
                ManageSessionOrderAction::DELETE_PERMISSION,
            ])
            ->where('guard_name', 'web')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
