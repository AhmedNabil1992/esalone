<?php

namespace App\Filament\Resources\Tenant\PlaySessionDetails\Pages;

use App\Filament\Resources\Tenant\PlaySessionDetails\PlaySessionDetailResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPlaySessionDetail extends EditRecord
{
    protected static string $resource = PlaySessionDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
