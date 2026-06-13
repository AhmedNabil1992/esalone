<?php

namespace App\Filament\Resources\Tenant\DeviceTypes\Pages;

use App\Filament\Resources\Tenant\DeviceTypes\DeviceTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDeviceType extends CreateRecord
{
    protected static string $resource = DeviceTypeResource::class;

    protected function getRedirectUrl(): string
    {
        // السطر ده بيقول لفيلامنت: بعد ما تخلص، رجعه لصفحة الجدول (index) الخاصة بنفس الـ Resource
        return $this->getResource()::getUrl('index');
    }
}
