<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\WaitingList;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class WaitingListPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:WaitingList');
    }

    public function view(AuthUser $authUser, WaitingList $waitingList): bool
    {
        return $authUser->can('View:WaitingList');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:WaitingList');
    }

    public function update(AuthUser $authUser, WaitingList $waitingList): bool
    {
        return $authUser->can('Update:WaitingList');
    }

    public function delete(AuthUser $authUser, WaitingList $waitingList): bool
    {
        return $authUser->can('Delete:WaitingList');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:WaitingList');
    }
}
