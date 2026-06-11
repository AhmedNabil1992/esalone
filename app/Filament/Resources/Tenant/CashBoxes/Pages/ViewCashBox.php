<?php

namespace App\Filament\Resources\Tenant\CashBoxes\Pages;

use App\Filament\Resources\Tenant\CashBoxes\CashBoxResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCashBox extends ViewRecord
{
    protected static string $resource = CashBoxResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
