<?php

namespace App\Filament\Resources\Tenant\CashBoxes\Pages;

use App\Filament\Resources\Tenant\CashBoxes\CashBoxResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCashBox extends EditRecord
{
    protected static string $resource = CashBoxResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
