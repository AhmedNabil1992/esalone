<?php

namespace App\Filament\Resources\Tenant\SalonServices\Pages;

use App\Actions\Tenant\SaveSalonServiceAction;
use App\Filament\Resources\Tenant\SalonServices\SalonServiceResource;
use App\Models\Tenant\SalonService;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditSalonService extends EditRecord
{
    protected static string $resource = SalonServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof SalonService) {
            throw new \LogicException('Unexpected record type for salon service resource.');
        }

        return app(SaveSalonServiceAction::class)->execute($data, $record);
    }
}
