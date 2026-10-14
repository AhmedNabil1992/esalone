<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\StaffMember;
use App\Models\Tenant\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StaffMemberPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:StaffMember');
    }

    public function view(User $user, StaffMember $staffMember): bool
    {
        return $user->can('View:StaffMember') && $this->canAccessBranch($user, (int) $staffMember->branch_id);
    }

    public function create(User $user): bool
    {
        return $user->can('Create:StaffMember');
    }

    public function update(User $user, StaffMember $staffMember): bool
    {
        return $user->can('Update:StaffMember') && $this->canAccessBranch($user, (int) $staffMember->branch_id);
    }

    public function delete(User $user, StaffMember $staffMember): bool
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
