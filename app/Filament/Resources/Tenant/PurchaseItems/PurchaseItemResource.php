<?php

namespace App\Filament\Resources\Tenant\PurchaseItems;

use App\Filament\Resources\Tenant\PurchaseItems\Pages\CreatePurchaseItem;
use App\Filament\Resources\Tenant\PurchaseItems\Pages\EditPurchaseItem;
use App\Filament\Resources\Tenant\PurchaseItems\Pages\ListPurchaseItems;
use App\Filament\Resources\Tenant\PurchaseItems\Pages\ViewPurchaseItem;
use App\Filament\Resources\Tenant\PurchaseItems\Schemas\PurchaseItemForm;
use App\Filament\Resources\Tenant\PurchaseItems\Schemas\PurchaseItemInfolist;
use App\Filament\Resources\Tenant\PurchaseItems\Tables\PurchaseItemsTable;
use App\Models\Tenant\PurchaseItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class PurchaseItemResource extends Resource
{
    protected static ?string $model = PurchaseItem::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-queue-list';

    protected static string|UnitEnum|null $navigationGroup = 'المشتريات';

    protected static ?string $navigationLabel = 'بنود المشتريات';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'بند مشتريات';

    protected static ?string $pluralModelLabel = 'بنود المشتريات';

    public static function form(Schema $schema): Schema
    {
        return PurchaseItemForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PurchaseItemInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PurchaseItemsTable::configure($table);
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
            'index' => ListPurchaseItems::route('/'),
            'create' => CreatePurchaseItem::route('/create'),
            'view' => ViewPurchaseItem::route('/{record}'),
            'edit' => EditPurchaseItem::route('/{record}/edit'),
        ];
    }
}
