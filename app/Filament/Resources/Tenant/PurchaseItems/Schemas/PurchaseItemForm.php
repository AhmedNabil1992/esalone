<?php

namespace App\Filament\Resources\Tenant\PurchaseItems\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PurchaseItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('catalog_item_id')
                    ->label('الصنف')
                    ->relationship('item', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('quantity')
                    ->label('الكمية')
                    ->numeric(step: '0.001')
                    ->required(),
                TextInput::make('unit_cost')
                    ->label('سعر الوحدة')
                    ->numeric(decimalPlaces: 2)
                    ->required(),
                TextInput::make('total_cost')
                    ->label('التكلفة الإجمالية')
                    ->numeric(decimalPlaces: 2)
                    ->readOnly(),
            ]);
    }
}
