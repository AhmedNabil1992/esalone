<?php

namespace App\Filament\Resources\Tenant\PlayPrices\Pages;

use App\Filament\Resources\Tenant\PlayPrices\PlayPriceResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPlayPrice extends ViewRecord
{
    protected static string $resource = PlayPriceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
