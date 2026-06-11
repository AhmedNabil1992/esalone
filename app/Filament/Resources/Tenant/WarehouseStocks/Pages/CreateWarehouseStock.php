<?php

namespace App\Filament\Resources\Tenant\WarehouseStocks\Pages;

use App\Filament\Resources\Tenant\WarehouseStocks\WarehouseStockResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWarehouseStock extends CreateRecord
{
    protected static string $resource = WarehouseStockResource::class;
}
