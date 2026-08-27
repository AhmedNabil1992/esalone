<?php

namespace App\Filament\Customer\Resources\CustomerPayments;

use App\Filament\Customer\Resources\CustomerPayments\Pages\ListCustomerPayments;
use App\Filament\Customer\Resources\CustomerPayments\Schemas\CustomerPaymentsForm;
use App\Filament\Customer\Resources\CustomerPayments\Tables\CustomerPaymentsTable;
use App\Models\Tenant\CustomerCredit;
use App\Scopes\BranchScope;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomerPaymentsResource extends Resource
{
    protected static ?string $model = CustomerCredit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'سجل الرصيد';

    public static function getModelLabel(): string
    {
        return 'سجل الرصيد';
    }

    public static function getPluralModelLabel(): string
    {
        return 'سجل الرصيد';
    }

    public static function form(Schema $schema): Schema
    {
        return CustomerPaymentsForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomerPaymentsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScope(BranchScope::class)
            ->where('customer_id', auth()->guard('customer')->id())
            ->with(['branch', 'user'])
            ->orderBy('id', 'desc');
    }

    public static function canViewAny(): bool
    {
        return auth()->guard('customer')->check();
    }

    public static function canView($record): bool
    {
        return auth()->guard('customer')->check() && (int) $record->customer_id === (int) auth()->guard('customer')->id();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
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
            'index' => ListCustomerPayments::route('/'),
        ];
    }
}
