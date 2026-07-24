<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\OrderSessionDetail;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class OrderSessionDetailPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:OrderSessionDetail');
    }

    public function view(AuthUser $authUser, OrderSessionDetail $orderSessionDetail): bool
    {
        return $authUser->can('View:OrderSessionDetail');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:OrderSessionDetail');
    }

    public function update(AuthUser $authUser, OrderSessionDetail $orderSessionDetail): bool
    {
        return $authUser->can('Update:OrderSessionDetail');
    }

    public function delete(AuthUser $authUser, OrderSessionDetail $orderSessionDetail): bool
    {
        return $authUser->can('Delete:OrderSessionDetail');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:OrderSessionDetail');
    }
}
