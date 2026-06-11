<?php

namespace App\Filament\Resources\Tenant\PlayTypes\Pages;

use App\Filament\Resources\Tenant\PlayTypes\PlayTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPlayType extends EditRecord
{
    protected static string $resource = PlayTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
