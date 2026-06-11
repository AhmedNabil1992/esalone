<?php

namespace App\Filament\Resources\Tenant\FinancialTransactions;

use App\Filament\Resources\Tenant\FinancialTransactions\Pages\CreateFinancialTransaction;
use App\Filament\Resources\Tenant\FinancialTransactions\Pages\EditFinancialTransaction;
use App\Filament\Resources\Tenant\FinancialTransactions\Pages\ListFinancialTransactions;
use App\Filament\Resources\Tenant\FinancialTransactions\Pages\ViewFinancialTransaction;
use App\Filament\Resources\Tenant\FinancialTransactions\Schemas\FinancialTransactionForm;
use App\Filament\Resources\Tenant\FinancialTransactions\Schemas\FinancialTransactionInfolist;
use App\Filament\Resources\Tenant\FinancialTransactions\Tables\FinancialTransactionsTable;
use App\Models\Tenant\FinancialTransaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class FinancialTransactionResource extends Resource
{
    protected static ?string $model = FinancialTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';

    protected static string|UnitEnum|null $navigationGroup = 'الإدارة المالية';

    protected static ?string $navigationLabel = 'الحركات المالية';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'حركة مالية';

    protected static ?string $pluralModelLabel = 'الحركات المالية';

    public static function form(Schema $schema): Schema
    {
        return FinancialTransactionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return FinancialTransactionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FinancialTransactionsTable::configure($table);
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
            'index' => ListFinancialTransactions::route('/'),
            'create' => CreateFinancialTransaction::route('/create'),
            'view' => ViewFinancialTransaction::route('/{record}'),
            'edit' => EditFinancialTransaction::route('/{record}/edit'),
        ];
    }
}
