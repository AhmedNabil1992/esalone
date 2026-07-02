<?php

namespace App\Filament\Resources\Tenant\WaitingLists\Pages;

use App\Filament\Resources\Tenant\WaitingLists\WaitingListResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditWaitingList extends EditRecord
{
    protected static string $resource = WaitingListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
