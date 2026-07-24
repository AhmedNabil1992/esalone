<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use WallaceMartinss\FilamentEvolution\Models\WhatsappMessage;

class WhatsappMessagePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:WhatsappMessage');
    }

    public function view(AuthUser $authUser, WhatsappMessage $whatsappMessage): bool
    {
        return $authUser->can('View:WhatsappMessage');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:WhatsappMessage');
    }

    public function update(AuthUser $authUser, WhatsappMessage $whatsappMessage): bool
    {
        return $authUser->can('Update:WhatsappMessage');
    }

    public function delete(AuthUser $authUser, WhatsappMessage $whatsappMessage): bool
    {
        return $authUser->can('Delete:WhatsappMessage');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:WhatsappMessage');
    }
}
