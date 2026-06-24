<?php

namespace App\Filament\Resources\Tenant\ProductComponents;

use App\Filament\Resources\Tenant\ProductComponents\Pages\CreateProductComponent;
use App\Filament\Resources\Tenant\ProductComponents\Pages\EditProductComponent;
use App\Filament\Resources\Tenant\ProductComponents\Pages\ListProductComponents;
use App\Filament\Resources\Tenant\ProductComponents\Pages\ViewProductComponent;
use App\Filament\Resources\Tenant\ProductComponents\Schemas\ProductComponentForm;
use App\Filament\Resources\Tenant\ProductComponents\Schemas\ProductComponentInfolist;
use App\Filament\Resources\Tenant\ProductComponents\Tables\ProductComponentsTable;
use App\Models\Tenant\ProductComponent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class ProductComponentResource extends Resource
{
    protected static ?string $model = ProductComponent::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string|UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'مكونات المنتج (BOM)';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'مكوّن منتج';

    protected static ?string $pluralModelLabel = 'مكونات المنتج';

    public static function form(Schema $schema): Schema
    {
        return ProductComponentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProductComponentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductComponentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductComponents::route('/'),
            'create' => CreateProductComponent::route('/create'),
            'view' => ViewProductComponent::route('/{record}'),
            'edit' => EditProductComponent::route('/{record}/edit'),
        ];
    }
}
