<?php

namespace App\Filament\Resources\Tenant\InventoryGroups\Pages;

use App\Filament\Resources\Tenant\InventoryGroups\InventoryGroupResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewInventoryGroup extends ViewRecord
{
    protected static string $resource = InventoryGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
