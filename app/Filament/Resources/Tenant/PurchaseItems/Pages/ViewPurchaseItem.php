<?php

namespace App\Filament\Resources\Tenant\PurchaseItems\Pages;

use App\Filament\Resources\Tenant\PurchaseItems\PurchaseItemResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPurchaseItem extends ViewRecord
{
    protected static string $resource = PurchaseItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
