<?php

namespace App\Filament\Resources\Tenant\PlayTypes\Pages;

use App\Filament\Resources\Tenant\PlayTypes\PlayTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPlayTypes extends ListRecords
{
    protected static string $resource = PlayTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
