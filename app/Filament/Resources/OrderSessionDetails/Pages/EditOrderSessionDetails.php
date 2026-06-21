<?php

namespace App\Filament\Resources\OrderSessionDetails\Pages;

use App\Filament\Resources\OrderSessionDetails\OrderSessionDetailsResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOrderSessionDetails extends EditRecord
{
    protected static string $resource = OrderSessionDetailsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // DeleteAction::make(),
        ];
    }
}
