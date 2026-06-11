<?php

namespace App\Filament\Resources\Tenant\WarehouseStocks\Pages;

use App\Filament\Resources\Tenant\WarehouseStocks\WarehouseStockResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWarehouseStocks extends ListRecords
{
    protected static string $resource = WarehouseStockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
