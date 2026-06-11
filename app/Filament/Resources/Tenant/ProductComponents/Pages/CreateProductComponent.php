<?php

namespace App\Filament\Resources\Tenant\ProductComponents\Pages;

use App\Filament\Resources\Tenant\ProductComponents\ProductComponentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProductComponent extends CreateRecord
{
    protected static string $resource = ProductComponentResource::class;
}
