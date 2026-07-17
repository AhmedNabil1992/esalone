<?php

namespace App\Filament\Resources\Tenant\ProductComponents\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ProductComponentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('product.name')
                    ->label('المنتج الرئيسي'),
                TextEntry::make('component.name')
                    ->label('المكون (الخامة)'),
                TextEntry::make('quantity')
                    ->label('الكمية المطلوبة'),
                TextEntry::make('notes')
                    ->label('ملاحظات')
                    ->placeholder('-'),
                TextEntry::make('sort_order')
                    ->label('ترتيب العرض'),
            ]);
    }
}
