<?php

namespace App\Filament\Resources\Tenant\InventoryGroups\Pages;

use App\Filament\Resources\Tenant\InventoryGroups\InventoryGroupResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditInventoryGroup extends EditRecord
{
    protected static string $resource = InventoryGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
