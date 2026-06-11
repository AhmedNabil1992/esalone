<?php

namespace App\Filament\Resources\Tenant\WarehouseStocks\Pages;

use App\Filament\Resources\Tenant\WarehouseStocks\WarehouseStockResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewWarehouseStock extends ViewRecord
{
    protected static string $resource = WarehouseStockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
