<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\DirectSale;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class DirectSalePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('Create:DirectSale');
    }

    public function view(AuthUser $authUser, DirectSale $directSale): bool
    {
        return $authUser->can('Create:DirectSale');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:DirectSale');
    }
}
