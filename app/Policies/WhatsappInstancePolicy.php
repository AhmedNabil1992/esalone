<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use WallaceMartinss\FilamentEvolution\Models\WhatsappInstance;

class WhatsappInstancePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:WhatsappInstance');
    }

    public function view(AuthUser $authUser, WhatsappInstance $whatsappInstance): bool
    {
        return $authUser->can('View:WhatsappInstance');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:WhatsappInstance');
    }

    public function update(AuthUser $authUser, WhatsappInstance $whatsappInstance): bool
    {
        return $authUser->can('Update:WhatsappInstance');
    }

    public function delete(AuthUser $authUser, WhatsappInstance $whatsappInstance): bool
    {
        return $authUser->can('Delete:WhatsappInstance');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:WhatsappInstance');
    }
}
