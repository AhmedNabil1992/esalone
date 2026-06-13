<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\Purchase;
use App\Models\Tenant\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PurchasePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:Purchase');
    }

    public function view(User $user, Purchase $purchase): bool
    {
        return $user->can('View:Purchase');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:Purchase');
    }

    public function update(User $user, Purchase $purchase): bool
    {
        return false;
    }

    public function delete(User $user, Purchase $purchase): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, Purchase $purchase): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, Purchase $purchase): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    public function replicate(User $user): bool
    {
        return false;
    }

    public function reorder(User $user): bool
    {
        return false;
    }
}
