<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\PlaySessionDetail;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class PlaySessionDetailPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PlaySessionDetail');
    }

    public function view(AuthUser $authUser, PlaySessionDetail $playSessionDetail): bool
    {
        return $authUser->can('View:PlaySessionDetail');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PlaySessionDetail');
    }

    public function update(AuthUser $authUser, PlaySessionDetail $playSessionDetail): bool
    {
        return $authUser->can('Update:PlaySessionDetail');
    }

    public function delete(AuthUser $authUser, PlaySessionDetail $playSessionDetail): bool
    {
        return $authUser->can('Delete:PlaySessionDetail');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:PlaySessionDetail');
    }
}
