<?php

namespace App\Filament\Resources\Tenant\InventoryGroups;

use App\Filament\Resources\Tenant\InventoryGroups\Pages\CreateInventoryGroup;
use App\Filament\Resources\Tenant\InventoryGroups\Pages\EditInventoryGroup;
use App\Filament\Resources\Tenant\InventoryGroups\Pages\ListInventoryGroups;
use App\Filament\Resources\Tenant\InventoryGroups\Pages\ViewInventoryGroup;
use App\Filament\Resources\Tenant\InventoryGroups\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\Tenant\InventoryGroups\Schemas\InventoryGroupForm;
use App\Filament\Resources\Tenant\InventoryGroups\Schemas\InventoryGroupInfolist;
use App\Filament\Resources\Tenant\InventoryGroups\Tables\InventoryGroupsTable;
use App\Models\Tenant\InventoryGroup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class InventoryGroupResource extends Resource
{
    protected static ?string $model = InventoryGroup::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-folder';

    protected static string|UnitEnum|null $navigationGroup = 'إدارة المخزون';

    protected static ?string $navigationLabel = 'مجموعات الأصناف';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'مجموعات الأصناف';

    protected static ?string $pluralModelLabel = 'مجموعات الأصناف';

    public static function form(Schema $schema): Schema
    {
        return InventoryGroupForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return InventoryGroupInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InventoryGroupsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInventoryGroups::route('/'),
            'create' => CreateInventoryGroup::route('/create'),
            'view' => ViewInventoryGroup::route('/{record}'),
            'edit' => EditInventoryGroup::route('/{record}/edit'),
        ];
    }
}
