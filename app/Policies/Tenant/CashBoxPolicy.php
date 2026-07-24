<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\CashBox;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class CashBoxPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CashBox');
    }

    public function view(AuthUser $authUser, CashBox $cashBox): bool
    {
        return $authUser->can('View:CashBox');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CashBox');
    }

    public function update(AuthUser $authUser, CashBox $cashBox): bool
    {
        return $authUser->can('Update:CashBox');
    }

    public function delete(AuthUser $authUser, CashBox $cashBox): bool
    {
        return $authUser->can('Delete:CashBox');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CashBox');
    }
}
