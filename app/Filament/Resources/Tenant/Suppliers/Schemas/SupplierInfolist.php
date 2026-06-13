<?php

namespace App\Filament\Resources\Tenant\Suppliers\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class SupplierInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label('اسم المورد'),
                TextEntry::make('phone')
                    ->label('رقم الهاتف')
                    ->placeholder('-'),
                TextEntry::make('email')
                    ->label('البريد الإلكتروني')
                    ->placeholder('-'),
                TextEntry::make('address')
                    ->label('العنوان')
                    ->placeholder('-'),
                TextEntry::make('is_active')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'نشط' : 'غير نشط'),
            ]);
    }
}
