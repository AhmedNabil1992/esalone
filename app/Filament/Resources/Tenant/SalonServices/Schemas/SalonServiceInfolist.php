<?php

namespace App\Filament\Resources\Tenant\SalonServices\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class SalonServiceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label('اسم الخدمة'),
                TextEntry::make('category')
                    ->label('التصنيف'),
                TextEntry::make('duration_minutes')
                    ->label('المدة بالدقائق'),
                TextEntry::make('price')
                    ->label('السعر')
                    ->money('EGP'),
                TextEntry::make('description')
                    ->label('الوصف')
                    ->placeholder('—')
                    ->columnSpanFull(),
                IconEntry::make('is_active')
                    ->label('متاحة للحجز')
                    ->boolean(),
            ]);
    }
}
