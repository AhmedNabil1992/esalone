<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tenant\Invoices;

use App\Filament\Resources\Tenant\Invoices\Pages\ListInvoices;
use App\Filament\Resources\Tenant\Invoices\Pages\ViewInvoice;
use App\Filament\Resources\Tenant\Invoices\RelationManagers\InvoiceItemsRelationManager;
use App\Filament\Resources\Tenant\Invoices\RelationManagers\InvoicePaymentsRelationManager;
use App\Filament\Resources\Tenant\Invoices\RelationManagers\PlaySessionDetailsRelationManager;
use App\Filament\Resources\Tenant\Invoices\Schemas\InvoiceInfolist;
use App\Filament\Resources\Tenant\Invoices\Tables\InvoicesTable;
use App\Models\Tenant\Invoice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-receipt-refund';

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'سجل الفواتير';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'فاتورة';

    protected static ?string $pluralModelLabel = 'سجل الفواتير';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            'device',
            'customer',
            'cashBox',
            'shift',
            'createdBy',
            'marketHistories',
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return InvoiceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InvoicesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            InvoicePaymentsRelationManager::class,
            PlaySessionDetailsRelationManager::class,
            InvoiceItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvoices::route('/'),
            'view' => ViewInvoice::route('/{record}'),
        ];
    }
}
