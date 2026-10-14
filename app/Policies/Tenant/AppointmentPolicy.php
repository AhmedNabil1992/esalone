<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\Appointment;
use App\Models\Tenant\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AppointmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:Appointment');
    }

    public function view(User $user, Appointment $appointment): bool
    {
        return $user->can('View:Appointment') && $this->canAccessBranch($user, (int) $appointment->branch_id);
    }

    public function create(User $user): bool
    {
        return $user->can('Create:Appointment');
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $user->can('Update:Appointment') && $this->canAccessBranch($user, (int) $appointment->branch_id);
    }

    public function delete(User $user, Appointment $appointment): bool
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
