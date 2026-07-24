<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\CatalogItem;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class CatalogItemPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CatalogItem');
    }

    public function view(AuthUser $authUser, CatalogItem $catalogItem): bool
    {
        return $authUser->can('View:CatalogItem');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CatalogItem');
    }

    public function update(AuthUser $authUser, CatalogItem $catalogItem): bool
    {
        return $authUser->can('Update:CatalogItem');
    }

    public function delete(AuthUser $authUser, CatalogItem $catalogItem): bool
    {
        return $authUser->can('Delete:CatalogItem');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CatalogItem');
    }
}
