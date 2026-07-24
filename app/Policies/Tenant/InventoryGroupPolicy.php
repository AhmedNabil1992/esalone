<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\InventoryGroup;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class InventoryGroupPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:InventoryGroup');
    }

    public function view(AuthUser $authUser, InventoryGroup $inventoryGroup): bool
    {
        return $authUser->can('View:InventoryGroup');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:InventoryGroup');
    }

    public function update(AuthUser $authUser, InventoryGroup $inventoryGroup): bool
    {
        return $authUser->can('Update:InventoryGroup');
    }

    public function delete(AuthUser $authUser, InventoryGroup $inventoryGroup): bool
    {
        return $authUser->can('Delete:InventoryGroup');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:InventoryGroup');
    }
}
