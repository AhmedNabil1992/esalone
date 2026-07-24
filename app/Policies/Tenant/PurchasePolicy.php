<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\Purchase;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class PurchasePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Purchase');
    }

    public function view(AuthUser $authUser, Purchase $purchase): bool
    {
        return $authUser->can('View:Purchase');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Purchase');
    }

    public function update(AuthUser $authUser, Purchase $purchase): bool
    {
        return $authUser->can('Update:Purchase');
    }

    public function delete(AuthUser $authUser, Purchase $purchase): bool
    {
        return $authUser->can('Delete:Purchase');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Purchase');
    }
}
