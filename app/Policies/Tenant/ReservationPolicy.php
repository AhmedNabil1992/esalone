<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\Reservation;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ReservationPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Reservation');
    }

    public function view(AuthUser $authUser, Reservation $reservation): bool
    {
        return $authUser->can('View:Reservation');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Reservation');
    }

    public function update(AuthUser $authUser, Reservation $reservation): bool
    {
        return $authUser->can('Update:Reservation');
    }

    public function delete(AuthUser $authUser, Reservation $reservation): bool
    {
        return $authUser->can('Delete:Reservation');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Reservation');
    }
}
