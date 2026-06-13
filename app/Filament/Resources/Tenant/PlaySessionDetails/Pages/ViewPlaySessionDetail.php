<?php

namespace App\Filament\Resources\Tenant\PlaySessionDetails\Pages;

use App\Filament\Resources\Tenant\PlaySessionDetails\PlaySessionDetailResource;
use Filament\Resources\Pages\ViewRecord;

class ViewPlaySessionDetail extends ViewRecord
{
    protected static string $resource = PlaySessionDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
