<?php

namespace App\Filament\Resources\Tenant\PurchaseItems\Pages;

use App\Filament\Resources\Tenant\PurchaseItems\PurchaseItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchaseItem extends CreateRecord
{
    protected static string $resource = PurchaseItemResource::class;
}
