<?php

namespace App\Filament\Resources\Tenant\ProductComponents\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ProductComponentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->label('المنتج الرئيسي')
                    ->relationship('product', 'name', fn ($query) => $query->where('is_product', true))
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('component_id')
                    ->label('المكون (الخامة)')
                    ->relationship('component', 'name', fn ($query) => $query->where('is_component', true))
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('quantity')
                    ->label('الكمية المطلوبة (لكل وحدة منتج)')
                    ->numeric()
                    ->minValue(0.001)
                    ->required(),
                TextInput::make('notes')
                    ->label('ملاحظات')
                    ->maxLength(100),
                TextInput::make('sort_order')
                    ->label('ترتيب العرض')
                    ->numeric()
                    ->default(0),
            ]);
    }
}
