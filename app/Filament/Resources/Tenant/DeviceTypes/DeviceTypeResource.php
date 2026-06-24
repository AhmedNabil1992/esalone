<?php

namespace App\Filament\Resources\Tenant\DeviceTypes;

use App\Filament\Resources\Tenant\DeviceTypes\Pages\CreateDeviceType;
use App\Filament\Resources\Tenant\DeviceTypes\Pages\EditDeviceType;
use App\Filament\Resources\Tenant\DeviceTypes\Pages\ListDeviceTypes;
use App\Filament\Resources\Tenant\DeviceTypes\Pages\ViewDeviceType;
use App\Filament\Resources\Tenant\DeviceTypes\RelationManagers\DevicesRelationManager;
use App\Filament\Resources\Tenant\DeviceTypes\Schemas\DeviceTypeForm;
use App\Filament\Resources\Tenant\DeviceTypes\Schemas\DeviceTypeInfolist;
use App\Filament\Resources\Tenant\DeviceTypes\Tables\DeviceTypesTable;
use App\Models\Tenant\DevicesType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class DeviceTypeResource extends Resource
{
    protected static ?string $model = DevicesType::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-computer-desktop';

    protected static string|UnitEnum|null $navigationGroup = 'إدارة الأجهزة';

    protected static ?string $navigationLabel = 'أنواع الأجهزة';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'نوع جهاز';

    protected static ?string $pluralModelLabel = 'أنواع الأجهزة';

    public static function form(Schema $schema): Schema
    {
        return DeviceTypeForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DeviceTypeInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DeviceTypesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            DevicesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDeviceTypes::route('/'),
            'create' => CreateDeviceType::route('/create'),
            'view' => ViewDeviceType::route('/{record}'),
            'edit' => EditDeviceType::route('/{record}/edit'),
        ];
    }
}
