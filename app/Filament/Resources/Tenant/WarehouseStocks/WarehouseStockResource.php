<?php

namespace App\Filament\Resources\Tenant\WarehouseStocks;

use App\Filament\Resources\Tenant\WarehouseStocks\Pages\CreateWarehouseStock;
use App\Filament\Resources\Tenant\WarehouseStocks\Pages\EditWarehouseStock;
use App\Filament\Resources\Tenant\WarehouseStocks\Pages\ListWarehouseStocks;
use App\Filament\Resources\Tenant\WarehouseStocks\Pages\ViewWarehouseStock;
use App\Filament\Resources\Tenant\WarehouseStocks\Schemas\WarehouseStockForm;
use App\Filament\Resources\Tenant\WarehouseStocks\Schemas\WarehouseStockInfolist;
use App\Filament\Resources\Tenant\WarehouseStocks\Tables\WarehouseStocksTable;
use App\Models\Tenant\WarehouseStock;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class WarehouseStockResource extends Resource
{
    protected static ?string $model = WarehouseStock::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static string|UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'أرصدة المخزون';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'رصيد مخزون';

    protected static ?string $pluralModelLabel = 'أرصدة المخزون';

    public static function form(Schema $schema): Schema
    {
        return WarehouseStockForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return WarehouseStockInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WarehouseStocksTable::configure($table);
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
            'index' => ListWarehouseStocks::route('/'),
            // 'create' => CreateWarehouseStock::route('/create'),
            'view' => ViewWarehouseStock::route('/{record}'),
            'edit' => EditWarehouseStock::route('/{record}/edit'),
        ];
    }
}
