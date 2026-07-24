<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\CashBoxLedgerEntry;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class CashBoxLedgerEntryPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CashBoxLedgerEntry');
    }

    public function view(AuthUser $authUser, CashBoxLedgerEntry $cashBoxLedgerEntry): bool
    {
        return $authUser->can('View:CashBoxLedgerEntry');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CashBoxLedgerEntry');
    }

    public function update(AuthUser $authUser, CashBoxLedgerEntry $cashBoxLedgerEntry): bool
    {
        return $authUser->can('Update:CashBoxLedgerEntry');
    }

    public function delete(AuthUser $authUser, CashBoxLedgerEntry $cashBoxLedgerEntry): bool
    {
        return $authUser->can('Delete:CashBoxLedgerEntry');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CashBoxLedgerEntry');
    }
}
