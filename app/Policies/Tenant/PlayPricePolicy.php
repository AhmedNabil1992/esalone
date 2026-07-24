<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\PlayPrice;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class PlayPricePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PlayPrice');
    }

    public function view(AuthUser $authUser, PlayPrice $playPrice): bool
    {
        return $authUser->can('View:PlayPrice');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PlayPrice');
    }

    public function update(AuthUser $authUser, PlayPrice $playPrice): bool
    {
        return $authUser->can('Update:PlayPrice');
    }

    public function delete(AuthUser $authUser, PlayPrice $playPrice): bool
    {
        return $authUser->can('Delete:PlayPrice');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:PlayPrice');
    }
}
