<?php

namespace App\Filament\Resources\Tenant\CashBoxes\Pages;

use App\Filament\Resources\Tenant\CashBoxes\CashBoxResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCashBoxes extends ListRecords
{
    protected static string $resource = CashBoxResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
