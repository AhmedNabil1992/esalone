<?php

namespace App\Filament\Resources\Tenant\StockMovements\Pages;

use App\Filament\Resources\Tenant\StockMovements\StockMovementResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewStockMovement extends ViewRecord
{
    protected static string $resource = StockMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
