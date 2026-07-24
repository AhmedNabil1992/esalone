<?php

namespace App\Filament\Resources\Tenant\Warehouses;

use App\Filament\Resources\Tenant\Warehouses\Pages\CreateWarehouse;
use App\Filament\Resources\Tenant\Warehouses\Pages\EditWarehouse;
use App\Filament\Resources\Tenant\Warehouses\Pages\ListWarehouses;
use App\Filament\Resources\Tenant\Warehouses\Pages\ViewWarehouse;
use App\Filament\Resources\Tenant\Warehouses\Schemas\WarehouseForm;
use App\Filament\Resources\Tenant\Warehouses\Schemas\WarehouseInfolist;
use App\Filament\Resources\Tenant\Warehouses\Tables\WarehousesTable;
use App\Models\Tenant\Warehouse;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class WarehouseResource extends Resource
{
    protected static ?string $model = Warehouse::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    protected static string|UnitEnum|null $navigationGroup = 'إدارة المخزون';

    protected static ?string $navigationLabel = 'المخازن';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'المخازن';

    protected static ?string $pluralModelLabel = 'المخازن';

    public static function form(Schema $schema): Schema
    {
        return WarehouseForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return WarehouseInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WarehousesTable::configure($table);
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
            'index' => ListWarehouses::route('/'),
            'create' => CreateWarehouse::route('/create'),
            'view' => ViewWarehouse::route('/{record}'),
            'edit' => EditWarehouse::route('/{record}/edit'),
        ];
    }
}
