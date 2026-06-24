<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tenant\CashBoxLedgerEntries;

use App\Filament\Resources\Tenant\CashBoxLedgerEntries\Pages\ListCashBoxLedgerEntries;
use App\Filament\Resources\Tenant\CashBoxLedgerEntries\Pages\ViewCashBoxLedgerEntry;
use App\Filament\Resources\Tenant\CashBoxLedgerEntries\Schemas\CashBoxLedgerEntryInfolist;
use App\Filament\Resources\Tenant\CashBoxLedgerEntries\Tables\CashBoxLedgerEntriesTable;
use App\Models\Tenant\CashBoxLedgerEntry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CashBoxLedgerEntryResource extends Resource
{
    protected static ?string $model = CashBoxLedgerEntry::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static string|UnitEnum|null $navigationGroup = 'Financial';

    protected static ?string $navigationLabel = 'سجل حركة الخزنة';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'حركة خزنة';

    protected static ?string $pluralModelLabel = 'سجل حركة الخزنة';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['cashBox', 'createdBy', 'shift']);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CashBoxLedgerEntryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CashBoxLedgerEntriesTable::configure($table);
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
            'index' => ListCashBoxLedgerEntries::route('/'),
            'view' => ViewCashBoxLedgerEntry::route('/{record}'),
        ];
    }
}
