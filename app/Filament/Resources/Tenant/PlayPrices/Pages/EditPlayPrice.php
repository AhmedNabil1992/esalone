<?php

namespace App\Filament\Resources\Tenant\PlayPrices\Pages;

use App\Filament\Resources\Tenant\PlayPrices\PlayPriceResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPlayPrice extends EditRecord
{
    protected static string $resource = PlayPriceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();
        $data['device_type_id'] = $record->device?->device_type_id ?? $record->playType?->device_type_id;
        $data['device_ids'] = [$record->device_id];

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['device_ids'])) {
            $data['device_id'] = is_array($data['device_ids']) ? ($data['device_ids'][0] ?? null) : $data['device_ids'];
            unset($data['device_ids']);
        }
        unset($data['device_type_id']);

        return $data;
    }
}
