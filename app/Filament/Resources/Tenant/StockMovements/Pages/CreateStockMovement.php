<?php

namespace App\Filament\Resources\Tenant\StockMovements\Pages;

use App\Filament\Resources\Tenant\StockMovements\StockMovementResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStockMovement extends CreateRecord
{
    protected static string $resource = StockMovementResource::class;
}
