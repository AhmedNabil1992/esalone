<?php

namespace App\Filament\Resources\Tenant\WaitingLists\Pages;

use App\Filament\Resources\Tenant\WaitingLists\WaitingListResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWaitingList extends CreateRecord
{
    protected static string $resource = WaitingListResource::class;
}
