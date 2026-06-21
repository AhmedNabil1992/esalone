<?php

namespace App\Filament\Resources\Tenant\Offers\Pages;

use App\Filament\Resources\Tenant\Offers\OfferResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOffer extends CreateRecord
{
    protected static string $resource = OfferResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
