<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\SalonService;
use App\Models\Tenant\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SalonServicePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:SalonService');
    }

    public function view(User $user, SalonService $salonService): bool
    {
        return $user->can('View:SalonService') && $this->canAccessBranch($user, (int) $salonService->branch_id);
    }

    public function create(User $user): bool
    {
        return $user->can('Create:SalonService');
    }

    public function update(User $user, SalonService $salonService): bool
    {
        return $user->can('Update:SalonService') && $this->canAccessBranch($user, (int) $salonService->branch_id);
    }

    public function delete(User $user, SalonService $salonService): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    private function canAccessBranch(User $user, int $branchId): bool
    {
        return $user->hasAnyRole(['owner', 'super_admin', 'Admin'])
            || in_array($branchId, $user->assignedBranchIds(), true);
    }
}
