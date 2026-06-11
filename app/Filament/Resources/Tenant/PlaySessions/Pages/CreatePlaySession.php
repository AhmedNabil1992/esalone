<?php

namespace App\Filament\Resources\Tenant\PlaySessions\Pages;

use App\Filament\Resources\Tenant\PlaySessions\PlaySessionResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePlaySession extends CreateRecord
{
    protected static string $resource = PlaySessionResource::class;
}
