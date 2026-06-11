<?php

namespace App\Filament\Resources\Tenant\PlayPrices\Pages;

use App\Filament\Resources\Tenant\PlayPrices\PlayPriceResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPlayPrice extends EditRecord
{
    protected static string $resource = PlayPriceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
