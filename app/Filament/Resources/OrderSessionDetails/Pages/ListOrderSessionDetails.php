<?php

namespace App\Filament\Resources\OrderSessionDetails\Pages;

use App\Filament\Resources\OrderSessionDetails\OrderSessionDetailsResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOrderSessionDetails extends ListRecords
{
    protected static string $resource = OrderSessionDetailsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make(),
        ];
    }
}
