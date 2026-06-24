<?php

namespace App\Filament\Resources\Tenant\StockMovements;

use App\Filament\Resources\Tenant\StockMovements\Pages\CreateStockMovement;
use App\Filament\Resources\Tenant\StockMovements\Pages\EditStockMovement;
use App\Filament\Resources\Tenant\StockMovements\Pages\ListStockMovements;
use App\Filament\Resources\Tenant\StockMovements\Pages\ViewStockMovement;
use App\Filament\Resources\Tenant\StockMovements\Schemas\StockMovementForm;
use App\Filament\Resources\Tenant\StockMovements\Schemas\StockMovementInfolist;
use App\Filament\Resources\Tenant\StockMovements\Tables\StockMovementsTable;
use App\Models\Tenant\StockMovement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static string|UnitEnum|null $navigationGroup = 'إدارة المخزون';

    protected static ?string $navigationLabel = 'حركات المخزون';

    protected static ?int $navigationSort = 6;

    protected static ?string $modelLabel = 'حركة مخزون';

    protected static ?string $pluralModelLabel = 'حركات المخزون';

    public static function form(Schema $schema): Schema
    {
        return StockMovementForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return StockMovementInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StockMovementsTable::configure($table);
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
            'index' => ListStockMovements::route('/'),
            'create' => CreateStockMovement::route('/create'),
            'view' => ViewStockMovement::route('/{record}'),
            'edit' => EditStockMovement::route('/{record}/edit'),
        ];
    }
}
