<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\ProductComponent;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ProductComponentPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ProductComponent');
    }

    public function view(AuthUser $authUser, ProductComponent $productComponent): bool
    {
        return $authUser->can('View:ProductComponent');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ProductComponent');
    }

    public function update(AuthUser $authUser, ProductComponent $productComponent): bool
    {
        return $authUser->can('Update:ProductComponent');
    }

    public function delete(AuthUser $authUser, ProductComponent $productComponent): bool
    {
        return $authUser->can('Delete:ProductComponent');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ProductComponent');
    }
}
