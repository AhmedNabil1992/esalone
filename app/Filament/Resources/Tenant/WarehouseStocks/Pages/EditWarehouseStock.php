<?php

namespace App\Filament\Resources\Tenant\WarehouseStocks\Pages;

use App\Filament\Resources\Tenant\WarehouseStocks\WarehouseStockResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditWarehouseStock extends EditRecord
{
    protected static string $resource = WarehouseStockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
