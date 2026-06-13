<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\Shift;
use App\Models\Tenant\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ShiftPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:Shift');
    }

    public function view(User $user, Shift $shift): bool
    {
        return $user->can('View:Shift');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:Shift');
    }

    public function update(User $user, Shift $shift): bool
    {
        return $user->can('Update:Shift');
    }

    public function delete(User $user, Shift $shift): bool
    {
        return $user->can('Delete:Shift');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:Shift');
    }

    public function restore(User $user, Shift $shift): bool
    {
        return $user->can('Restore:Shift');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:Shift');
    }

    public function forceDelete(User $user, Shift $shift): bool
    {
        return $user->can('ForceDelete:Shift');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:Shift');
    }

    public function replicate(User $user): bool
    {
        return $user->can('Replicate:Shift');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:Shift');
    }
}
