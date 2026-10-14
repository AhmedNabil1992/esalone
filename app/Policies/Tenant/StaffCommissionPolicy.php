<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\StaffCommission;
use App\Models\Tenant\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StaffCommissionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:StaffMember');
    }

    public function view(User $user, StaffCommission $commission): bool
    {
        return $user->can('View:StaffMember')
            && $this->canAccessBranch($user, (int) $commission->branch_id);
    }

    public function payout(User $user, StaffCommission $commission): bool
    {
        return $user->can('Update:StaffMember')
            && $this->canAccessBranch($user, (int) $commission->branch_id);
    }

    private function canAccessBranch(User $user, int $branchId): bool
    {
        return $user->hasAnyRole(['owner', 'super_admin', 'Admin'])
            || in_array($branchId, $user->assignedBranchIds(), true);
    }
}
