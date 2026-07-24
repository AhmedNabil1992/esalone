<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\Devices;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class DevicesPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Devices');
    }

    public function view(AuthUser $authUser, Devices $devices): bool
    {
        return $authUser->can('View:Devices');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Devices');
    }

    public function update(AuthUser $authUser, Devices $devices): bool
    {
        return $authUser->can('Update:Devices');
    }

    public function delete(AuthUser $authUser, Devices $devices): bool
    {
        return $authUser->can('Delete:Devices');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Devices');
    }
}
