<?php

namespace App\Filament\Resources\Tenant\PlayPrices\Pages;

use App\Filament\Resources\Tenant\PlayPrices\PlayPriceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPlayPrices extends ListRecords
{
    protected static string $resource = PlayPriceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
