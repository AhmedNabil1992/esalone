<?php

namespace App\Filament\Resources\Tenant\Warehouses\Pages;

use App\Filament\Resources\Tenant\Warehouses\WarehouseResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWarehouse extends CreateRecord
{
    protected static string $resource = WarehouseResource::class;

    protected function getRedirectUrl(): string
    {
        // السطر ده بيقول لفيلامنت: بعد ما تخلص، رجعه لصفحة الجدول (index) الخاصة بنفس الـ Resource
        return $this->getResource()::getUrl('index');
    }
}
