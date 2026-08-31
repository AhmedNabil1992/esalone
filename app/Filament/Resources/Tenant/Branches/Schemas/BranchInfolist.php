<?php

namespace App\Filament\Resources\Tenant\Branches\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BranchInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('تفاصيل الفرع')
                    ->schema([
                        TextEntry::make('name')
                            ->label('اسم الفرع'),
                        TextEntry::make('code')
                            ->label('كود الفرع')
                            ->badge(),
                        TextEntry::make('phone')
                            ->label('رقم الهاتف')
                            ->placeholder('-'),
                        TextEntry::make('address')
                            ->label('العنوان')
                            ->placeholder('-'),
                        IconEntry::make('is_active')
                            ->label('حالة النشاط')
                            ->boolean(),
                        TextEntry::make('created_at')
                            ->label('تاريخ الإنشاء')
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }
}
