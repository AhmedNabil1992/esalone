<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\CustomerCredit;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class CustomerCreditPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CustomerCredit');
    }

    public function view(AuthUser $authUser, CustomerCredit $customerCredit): bool
    {
        return $authUser->can('View:CustomerCredit');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CustomerCredit');
    }

    public function update(AuthUser $authUser, CustomerCredit $customerCredit): bool
    {
        return $authUser->can('Update:CustomerCredit');
    }

    public function delete(AuthUser $authUser, CustomerCredit $customerCredit): bool
    {
        return $authUser->can('Delete:CustomerCredit');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CustomerCredit');
    }
}
