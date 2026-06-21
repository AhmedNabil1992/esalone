<?php

namespace App\Filament\Resources\Tenant\Offers\Pages;

use App\Filament\Resources\Tenant\Offers\OfferResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewOffer extends ViewRecord
{
    protected static string $resource = OfferResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}
