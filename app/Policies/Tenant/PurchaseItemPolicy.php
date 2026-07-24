<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\PurchaseItem;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class PurchaseItemPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PurchaseItem');
    }

    public function view(AuthUser $authUser, PurchaseItem $purchaseItem): bool
    {
        return $authUser->can('View:PurchaseItem');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PurchaseItem');
    }

    public function update(AuthUser $authUser, PurchaseItem $purchaseItem): bool
    {
        return $authUser->can('Update:PurchaseItem');
    }

    public function delete(AuthUser $authUser, PurchaseItem $purchaseItem): bool
    {
        return $authUser->can('Delete:PurchaseItem');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:PurchaseItem');
    }
}
