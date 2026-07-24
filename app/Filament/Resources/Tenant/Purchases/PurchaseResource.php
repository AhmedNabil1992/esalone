<?php

namespace App\Filament\Resources\Tenant\Purchases;

use App\Filament\Resources\Tenant\Purchases\Pages\CreatePurchase;
use App\Filament\Resources\Tenant\Purchases\Pages\ListPurchases;
use App\Filament\Resources\Tenant\Purchases\Pages\ViewPurchase;
use App\Filament\Resources\Tenant\Purchases\RelationManagers\PurchaseItemsRelationManager;
use App\Filament\Resources\Tenant\Purchases\Schemas\PurchaseForm;
use App\Filament\Resources\Tenant\Purchases\Schemas\PurchaseInfolist;
use App\Filament\Resources\Tenant\Purchases\Tables\PurchasesTable;
use App\Models\Tenant\Purchase;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class PurchaseResource extends Resource
{
    protected static ?string $model = Purchase::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|UnitEnum|null $navigationGroup = 'المشتريات';

    protected static ?string $navigationLabel = 'فواتير المشتريات';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'فواتير المشتريات';

    protected static ?string $pluralModelLabel = 'فواتير المشتريات';

    public static function form(Schema $schema): Schema
    {
        return PurchaseForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PurchaseInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PurchasesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            PurchaseItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPurchases::route('/'),
            'create' => CreatePurchase::route('/create'),
            'view' => ViewPurchase::route('/{record}'),
        ];
    }
}
