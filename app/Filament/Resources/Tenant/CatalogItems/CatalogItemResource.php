<?php

namespace App\Filament\Resources\Tenant\CatalogItems;

use App\Filament\Resources\Tenant\CatalogItems\Pages\CreateCatalogItem;
use App\Filament\Resources\Tenant\CatalogItems\Pages\EditCatalogItem;
use App\Filament\Resources\Tenant\CatalogItems\Pages\ListCatalogItems;
use App\Filament\Resources\Tenant\CatalogItems\Pages\ViewCatalogItem;
use App\Filament\Resources\Tenant\CatalogItems\Schemas\CatalogItemForm;
use App\Filament\Resources\Tenant\CatalogItems\Schemas\CatalogItemInfolist;
use App\Filament\Resources\Tenant\CatalogItems\Tables\CatalogItemsTable;
use App\Models\Tenant\CatalogItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class CatalogItemResource extends Resource
{
    protected static ?string $model = CatalogItem::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static string|UnitEnum|null $navigationGroup = 'إدارة المخزون';

    protected static ?string $navigationLabel = 'الأصناف والمنتجات';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'الأصناف والمنتجات';

    protected static ?string $pluralModelLabel = 'الأصناف والمنتجات';

    public static function form(Schema $schema): Schema
    {
        return CatalogItemForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CatalogItemInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CatalogItemsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ComponentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCatalogItems::route('/'),
            'create' => CreateCatalogItem::route('/create'),
            'view' => ViewCatalogItem::route('/{record}'),
            'edit' => EditCatalogItem::route('/{record}/edit'),
        ];
    }
}
