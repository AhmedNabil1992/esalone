<?php

namespace App\Filament\Resources\Tenant\ExpensesTypes;

use App\Filament\Resources\Tenant\ExpensesTypes\Pages\CreateExpensesTypes;
use App\Filament\Resources\Tenant\ExpensesTypes\Pages\EditExpensesTypes;
use App\Filament\Resources\Tenant\ExpensesTypes\Pages\ListExpensesTypes;
use App\Filament\Resources\Tenant\ExpensesTypes\Schemas\ExpensesTypesForm;
use App\Filament\Resources\Tenant\ExpensesTypes\Tables\ExpensesTypesTable;
use App\Models\Tenant\ExpensesType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ExpensesTypesResource extends Resource
{
    protected static ?string $model = ExpensesType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'الإدارة المالية';

    protected static ?string $recordTitleAttribute = 'أنواع المصروفات';

    protected static ?string $navigationLabel = 'أنواع المصروفات';

    protected static ?string $pluralModelLabel = 'أنواع المصروفات';

    protected static ?string $modelLabel = 'أنواع المصروفات';

    public static function form(Schema $schema): Schema
    {
        return ExpensesTypesForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExpensesTypesTable::configure($table);
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
            'index' => ListExpensesTypes::route('/'),
            'create' => CreateExpensesTypes::route('/create'),
            'edit' => EditExpensesTypes::route('/{record}/edit'),
        ];
    }
}
