<?php

namespace App\Filament\Resources\Tenant\Offers\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class OfferInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            TextEntry::make('name')->label('اسم العرض'),
            TextEntry::make('devices')->label('الأجهزة'),
            TextEntry::make('play_type')->label('نوع اللعب'),
            TextEntry::make('period')->label('المدة')->suffix(' دقيقة'),
            TextEntry::make('price')->label('السعر')->money(),
            TextEntry::make('time_from')->label('من وقت')->time(),
            TextEntry::make('time_to')->label('إلى وقت')->time(),
            TextEntry::make('remark')->label('ملاحظات')->placeholder('—'),
            IconEntry::make('is_active')->label('مفعّل')->boolean(),
            TextEntry::make('created_at')->label('تاريخ الإضافة')->dateTime('Y-m-d h:i A'),
        ]);
    }
}
