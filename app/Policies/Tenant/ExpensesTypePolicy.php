<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\ExpensesType;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ExpensesTypePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ExpensesType');
    }

    public function view(AuthUser $authUser, ExpensesType $expensesType): bool
    {
        return $authUser->can('View:ExpensesType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ExpensesType');
    }

    public function update(AuthUser $authUser, ExpensesType $expensesType): bool
    {
        return $authUser->can('Update:ExpensesType');
    }

    public function delete(AuthUser $authUser, ExpensesType $expensesType): bool
    {
        return $authUser->can('Delete:ExpensesType');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ExpensesType');
    }
}
