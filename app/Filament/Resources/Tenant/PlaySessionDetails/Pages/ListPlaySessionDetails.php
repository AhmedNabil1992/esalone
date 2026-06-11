<?php

namespace App\Filament\Resources\Tenant\PlaySessionDetails\Pages;

use App\Filament\Resources\Tenant\PlaySessionDetails\PlaySessionDetailResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPlaySessionDetails extends ListRecords
{
    protected static string $resource = PlaySessionDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
