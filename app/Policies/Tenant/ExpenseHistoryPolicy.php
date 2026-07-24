<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\ExpenseHistory;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ExpenseHistoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ExpenseHistory') || $authUser->can('Create:ExpenseHistory');
    }

    public function view(AuthUser $authUser, ExpenseHistory $expenseHistory): bool
    {
        return $authUser->can('View:ExpenseHistory');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ExpenseHistory');
    }

    public function update(AuthUser $authUser, ExpenseHistory $expenseHistory): bool
    {
        return $authUser->can('Update:ExpenseHistory');
    }

    public function delete(AuthUser $authUser, ExpenseHistory $expenseHistory): bool
    {
        return $authUser->can('Delete:ExpenseHistory');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ExpenseHistory');
    }
}
