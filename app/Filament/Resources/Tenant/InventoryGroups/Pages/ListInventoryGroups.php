<?php

namespace App\Filament\Resources\Tenant\InventoryGroups\Pages;

use App\Filament\Resources\Tenant\InventoryGroups\InventoryGroupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInventoryGroups extends ListRecords
{
    protected static string $resource = InventoryGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
