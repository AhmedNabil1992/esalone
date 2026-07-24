<?php

namespace App\Filament\Resources\Tenant\Devices;

use App\Filament\Resources\Tenant\Devices\Pages\CreateDevice;
use App\Filament\Resources\Tenant\Devices\Pages\EditDevice;
use App\Filament\Resources\Tenant\Devices\Pages\ListDevices;
use App\Filament\Resources\Tenant\Devices\Pages\ViewDevice;
use App\Filament\Resources\Tenant\Devices\RelationManagers\PlayPricesRelationManager;
use App\Filament\Resources\Tenant\Devices\Schemas\DeviceForm;
use App\Filament\Resources\Tenant\Devices\Schemas\DeviceInfolist;
use App\Filament\Resources\Tenant\Devices\Tables\DevicesTable;
use App\Models\Tenant\Devices;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class DeviceResource extends Resource
{
    protected static ?string $model = Devices::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-device-tablet';

    protected static string|UnitEnum|null $navigationGroup = 'إدارة الأجهزة';

    protected static ?string $navigationLabel = 'الأجهزة';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'إدارة الأجهزة';

    protected static ?string $pluralModelLabel = 'الأجهزة';

    public static function form(Schema $schema): Schema
    {
        return DeviceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DeviceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DevicesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            PlayPricesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDevices::route('/'),
            'create' => CreateDevice::route('/create'),
            'view' => ViewDevice::route('/{record}'),
            'edit' => EditDevice::route('/{record}/edit'),
        ];
    }
}
