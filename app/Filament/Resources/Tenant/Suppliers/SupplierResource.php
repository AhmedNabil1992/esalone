<?php

namespace App\Filament\Resources\Tenant\Suppliers;

use App\Filament\Resources\Tenant\Suppliers\Pages\CreateSupplier;
use App\Filament\Resources\Tenant\Suppliers\Pages\EditSupplier;
use App\Filament\Resources\Tenant\Suppliers\Pages\ListSuppliers;
use App\Filament\Resources\Tenant\Suppliers\Pages\ViewSupplier;
use App\Filament\Resources\Tenant\Suppliers\Schemas\SupplierForm;
use App\Filament\Resources\Tenant\Suppliers\Schemas\SupplierInfolist;
use App\Filament\Resources\Tenant\Suppliers\Tables\SuppliersTable;
use App\Models\Tenant\Supplier;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static string|UnitEnum|null $navigationGroup = 'المشتريات';

    protected static ?string $navigationLabel = 'الموردين';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'مورد';

    protected static ?string $pluralModelLabel = 'الموردين';

    public static function form(Schema $schema): Schema
    {
        return SupplierForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SupplierInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SuppliersTable::configure($table);
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
            'index' => ListSuppliers::route('/'),
            'create' => CreateSupplier::route('/create'),
            'view' => ViewSupplier::route('/{record}'),
            'edit' => EditSupplier::route('/{record}/edit'),
        ];
    }
}
