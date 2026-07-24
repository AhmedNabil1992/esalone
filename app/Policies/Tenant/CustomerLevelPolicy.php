<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\CustomerLevel;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class CustomerLevelPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CustomerLevel');
    }

    public function view(AuthUser $authUser, CustomerLevel $customerLevel): bool
    {
        return $authUser->can('View:CustomerLevel');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CustomerLevel');
    }

    public function update(AuthUser $authUser, CustomerLevel $customerLevel): bool
    {
        return $authUser->can('Update:CustomerLevel');
    }

    public function delete(AuthUser $authUser, CustomerLevel $customerLevel): bool
    {
        return $authUser->can('Delete:CustomerLevel');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CustomerLevel');
    }
}
