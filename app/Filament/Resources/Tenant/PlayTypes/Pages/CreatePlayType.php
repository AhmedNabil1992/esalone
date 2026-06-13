<?php

namespace App\Filament\Resources\Tenant\PlayTypes\Pages;

use App\Filament\Resources\Tenant\PlayTypes\PlayTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePlayType extends CreateRecord
{
    protected static string $resource = PlayTypeResource::class;

    protected function getRedirectUrl(): string
    {
        // السطر ده بيقول لفيلامنت: بعد ما تخلص، رجعه لصفحة الجدول (index) الخاصة بنفس الـ Resource
        return $this->getResource()::getUrl('index');
    }
}
