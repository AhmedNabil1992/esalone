<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\WarehouseStock;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class WarehouseStockPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:WarehouseStock');
    }

    public function view(AuthUser $authUser, WarehouseStock $warehouseStock): bool
    {
        return $authUser->can('View:WarehouseStock');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:WarehouseStock');
    }

    public function update(AuthUser $authUser, WarehouseStock $warehouseStock): bool
    {
        return $authUser->can('Update:WarehouseStock');
    }

    public function delete(AuthUser $authUser, WarehouseStock $warehouseStock): bool
    {
        return $authUser->can('Delete:WarehouseStock');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:WarehouseStock');
    }
}
