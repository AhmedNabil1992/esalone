<?php

namespace App\Filament\Resources\Tenant\Customers\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CustomerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            TextEntry::make('name')->label('الاسم'),
            TextEntry::make('phone')->label('الهاتف'),
            TextEntry::make('email')->label('البريد الإلكتروني'),
            TextEntry::make('address')->label('العنوان'),
            TextEntry::make('balance')
                ->label('الرصيد الحالي')
                ->money()
                ->color(fn ($state) => $state < 0 ? 'danger' : ($state > 0 ? 'success' : 'gray')),
            IconEntry::make('is_active')->label('مفعّل')->boolean(),
            TextEntry::make('created_at')->label('تاريخ الإضافة')->dateTime('Y-m-d h:i A'),
        ]);
    }
}
