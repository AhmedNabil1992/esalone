<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\PlayType;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class PlayTypePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PlayType');
    }

    public function view(AuthUser $authUser, PlayType $playType): bool
    {
        return $authUser->can('View:PlayType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PlayType');
    }

    public function update(AuthUser $authUser, PlayType $playType): bool
    {
        return $authUser->can('Update:PlayType');
    }

    public function delete(AuthUser $authUser, PlayType $playType): bool
    {
        return $authUser->can('Delete:PlayType');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:PlayType');
    }
}
