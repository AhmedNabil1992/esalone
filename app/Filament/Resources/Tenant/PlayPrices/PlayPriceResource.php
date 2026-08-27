<?php

namespace App\Filament\Resources\Tenant\PlayPrices;

use App\Filament\Resources\Tenant\PlayPrices\Pages\CreatePlayPrice;
use App\Filament\Resources\Tenant\PlayPrices\Pages\EditPlayPrice;
use App\Filament\Resources\Tenant\PlayPrices\Pages\ListPlayPrices;
use App\Filament\Resources\Tenant\PlayPrices\Pages\ViewPlayPrice;
use App\Filament\Resources\Tenant\PlayPrices\Schemas\PlayPriceForm;
use App\Filament\Resources\Tenant\PlayPrices\Schemas\PlayPriceInfolist;
use App\Filament\Resources\Tenant\PlayPrices\Tables\PlayPricesTable;
use App\Models\Tenant\PlayPrice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class PlayPriceResource extends Resource
{
    protected static ?string $model = PlayPrice::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';

    protected static string|UnitEnum|null $navigationGroup = 'إدارة الأجهزة';

    protected static ?string $navigationLabel = 'أسعار اللعب';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'أسعار اللعب';

    protected static ?string $pluralModelLabel = 'أسعار اللعب';

    public static function form(Schema $schema): Schema
    {
        return PlayPriceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PlayPriceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PlayPricesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['device.branch', 'playType'])
            ->whereHas('device');
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
            'index' => ListPlayPrices::route('/'),
            'create' => CreatePlayPrice::route('/create'),
            'view' => ViewPlayPrice::route('/{record}'),
            'edit' => EditPlayPrice::route('/{record}/edit'),
        ];
    }
}
