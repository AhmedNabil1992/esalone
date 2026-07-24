<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\PlaySessions;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class PlaySessionsPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PlaySessions');
    }

    public function view(AuthUser $authUser, PlaySessions $playSessions): bool
    {
        return $authUser->can('View:PlaySessions');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PlaySessions');
    }

    public function update(AuthUser $authUser, PlaySessions $playSessions): bool
    {
        return $authUser->can('Update:PlaySessions');
    }

    public function delete(AuthUser $authUser, PlaySessions $playSessions): bool
    {
        return $authUser->can('Delete:PlaySessions');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:PlaySessions');
    }
}
