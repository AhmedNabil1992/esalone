<?php

namespace App\Filament\Resources\Tenant\CashBoxes;

use App\Filament\Resources\Tenant\CashBoxes\Pages\CreateCashBox;
use App\Filament\Resources\Tenant\CashBoxes\Pages\EditCashBox;
use App\Filament\Resources\Tenant\CashBoxes\Pages\ListCashBoxes;
use App\Filament\Resources\Tenant\CashBoxes\Pages\ViewCashBox;
use App\Filament\Resources\Tenant\CashBoxes\Schemas\CashBoxForm;
use App\Filament\Resources\Tenant\CashBoxes\Schemas\CashBoxInfolist;
use App\Filament\Resources\Tenant\CashBoxes\Tables\CashBoxesTable;
use App\Models\Tenant\CashBox;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class CashBoxResource extends Resource
{
    protected static ?string $model = CashBox::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|UnitEnum|null $navigationGroup = 'Financial';

    protected static ?string $navigationLabel = 'الخزن';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'خزنة';

    protected static ?string $pluralModelLabel = 'الخزن';

    public static function form(Schema $schema): Schema
    {
        return CashBoxForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CashBoxInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CashBoxesTable::configure($table);
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
            'index' => ListCashBoxes::route('/'),
            'create' => CreateCashBox::route('/create'),
            'view' => ViewCashBox::route('/{record}'),
            'edit' => EditCashBox::route('/{record}/edit'),
        ];
    }
}
