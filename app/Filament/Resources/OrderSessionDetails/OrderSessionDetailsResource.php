<?php

namespace App\Filament\Resources\OrderSessionDetails;

use App\Filament\Resources\OrderSessionDetails\Pages\CreateOrderSessionDetails;
use App\Filament\Resources\OrderSessionDetails\Pages\EditOrderSessionDetails;
use App\Filament\Resources\OrderSessionDetails\Pages\ListOrderSessionDetails;
use App\Filament\Resources\OrderSessionDetails\Schemas\OrderSessionDetailsForm;
use App\Filament\Resources\OrderSessionDetails\Tables\OrderSessionDetailsTable;
use App\Models\Tenant\OrderSessionDetail;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class OrderSessionDetailsResource extends Resource
{
    protected static ?string $model = OrderSessionDetail::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'اللعب';

    protected static ?string $navigationLabel = 'تقرير الطلبات الحالي';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'تقرير الطلبات الحالي';

    protected static ?string $pluralModelLabel = 'تقرير الطلبات الحالي';

    public static function form(Schema $schema): Schema
    {
        return OrderSessionDetailsForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrderSessionDetailsTable::configure($table);
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
            'index' => ListOrderSessionDetails::route('/'),
            'create' => CreateOrderSessionDetails::route('/create'),
            // 'edit' => EditOrderSessionDetails::route('/{record}/edit'),
        ];
    }
}
