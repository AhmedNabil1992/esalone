<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\DevicesType;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class DevicesTypePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:DevicesType');
    }

    public function view(AuthUser $authUser, DevicesType $devicesType): bool
    {
        return $authUser->can('View:DevicesType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:DevicesType');
    }

    public function update(AuthUser $authUser, DevicesType $devicesType): bool
    {
        return $authUser->can('Update:DevicesType');
    }

    public function delete(AuthUser $authUser, DevicesType $devicesType): bool
    {
        return $authUser->can('Delete:DevicesType');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:DevicesType');
    }
}
