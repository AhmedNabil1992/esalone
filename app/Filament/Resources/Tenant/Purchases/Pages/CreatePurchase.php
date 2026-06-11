<?php

namespace App\Filament\Resources\Tenant\Purchases\Pages;

use App\Filament\Resources\Tenant\Purchases\PurchaseResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchase extends CreateRecord
{
    protected static string $resource = PurchaseResource::class;
}
