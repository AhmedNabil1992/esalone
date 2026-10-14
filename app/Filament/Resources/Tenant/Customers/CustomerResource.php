<?php

namespace App\Filament\Resources\Tenant\Customers;

use App\Filament\Resources\Tenant\Customers\Pages\CreateCustomer;
use App\Filament\Resources\Tenant\Customers\Pages\EditCustomer;
use App\Filament\Resources\Tenant\Customers\Pages\ListCustomers;
use App\Filament\Resources\Tenant\Customers\Pages\ViewCustomer;
use App\Filament\Resources\Tenant\Customers\Schemas\CustomerForm;
use App\Filament\Resources\Tenant\Customers\Schemas\CustomerInfolist;
use App\Filament\Resources\Tenant\Customers\Tables\CustomersTable;
use App\Models\Tenant\Customer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|UnitEnum|null $navigationGroup = 'العملاء';

    protected static ?string $navigationLabel = 'العملاء';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'العملاء';

    protected static ?string $pluralModelLabel = 'العملاء';

    public static function form(Schema $schema): Schema
    {
        return CustomerForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CustomerInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomersTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withMax([
            'invoices' => fn (Builder $query): Builder => $query
                ->where('status', 'posted')
                ->whereNotNull('issued_at'),
        ], 'issued_at');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'create' => CreateCustomer::route('/create'),
            'view' => ViewCustomer::route('/{record}'),
            'edit' => EditCustomer::route('/{record}/edit'),
        ];
    }
}
