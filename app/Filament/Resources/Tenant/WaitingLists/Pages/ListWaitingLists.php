<?php

namespace App\Filament\Resources\Tenant\WaitingLists\Pages;

use App\Filament\Resources\Tenant\WaitingLists\WaitingListResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWaitingLists extends ListRecords
{
    protected static string $resource = WaitingListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
