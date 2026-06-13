<?php

namespace App\Filament\Resources\Tenant\InventoryGroups\Pages;

use App\Filament\Resources\Tenant\InventoryGroups\InventoryGroupResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInventoryGroup extends CreateRecord
{
    protected static string $resource = InventoryGroupResource::class;

    protected function getRedirectUrl(): string
    {
        // السطر ده بيقول لفيلامنت: بعد ما تخلص، رجعه لصفحة الجدول (index) الخاصة بنفس الـ Resource
        return $this->getResource()::getUrl('index');
    }
}
