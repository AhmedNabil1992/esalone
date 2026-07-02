<?php

namespace App\Filament\Resources\Tenant\CustomerLevels\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CustomerLevelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')
                ->label('اسم فئة العميل')
                ->required()
                ->maxLength(255),

            TextInput::make('points_required')
                ->label('النقاط التراكمية المطلوبة للترقية')
                ->numeric()
                ->default(0)
                ->required(),

            TextInput::make('discount_percent')
                ->label('نسبة الخصم الممنوحة (%)')
                ->numeric()
                ->default(0)
                ->required(),

            Select::make('applies_to')
                ->label('تطبيق الخصم على')
                ->options([
                    'play_only' => 'اللعب فقط',
                    'all' => 'اللعب وطلبات البوفيه معاً',
                ])
                ->default('play_only')
                ->required(),

            Toggle::make('is_active')
                ->label('نشطة')
                ->default(true),
        ]);
    }
}
