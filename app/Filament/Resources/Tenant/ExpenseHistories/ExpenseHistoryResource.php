<?php

namespace App\Filament\Resources\Tenant\ExpenseHistories;

use App\Filament\Resources\Tenant\ExpenseHistories\Pages\CreateExpenseHistory;
use App\Filament\Resources\Tenant\ExpenseHistories\Pages\ListExpenseHistories;
use App\Filament\Resources\Tenant\ExpenseHistories\Pages\ViewExpenseHistory;
use App\Filament\Resources\Tenant\ExpenseHistories\Schemas\ExpenseHistoryForm;
use App\Filament\Resources\Tenant\ExpenseHistories\Schemas\ExpenseHistoryInfolist;
use App\Filament\Resources\Tenant\ExpenseHistories\Tables\ExpenseHistoriesTable;
use App\Models\Tenant\ExpenseHistory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ExpenseHistoryResource extends Resource
{
    protected static ?string $model = ExpenseHistory::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';

    protected static string|UnitEnum|null $navigationGroup = 'Financial';

    protected static ?string $navigationLabel = 'سجل المصروفات';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'مصروف';

    protected static ?string $pluralModelLabel = 'سجل المصروفات';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('category', 'expense')
            ->where('direction', 'out');
    }

    public static function form(Schema $schema): Schema
    {
        return ExpenseHistoryForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ExpenseHistoryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExpenseHistoriesTable::configure($table);
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
            'index' => ListExpenseHistories::route('/'),
            'create' => CreateExpenseHistory::route('/create'),
            'view' => ViewExpenseHistory::route('/{record}'),
        ];
    }
}
