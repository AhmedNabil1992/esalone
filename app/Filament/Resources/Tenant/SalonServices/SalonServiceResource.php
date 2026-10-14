<?php

namespace App\Filament\Resources\Tenant\SalonServices;

use App\Filament\Resources\Tenant\SalonServices\Pages\CreateSalonService;
use App\Filament\Resources\Tenant\SalonServices\Pages\EditSalonService;
use App\Filament\Resources\Tenant\SalonServices\Pages\ListSalonServices;
use App\Filament\Resources\Tenant\SalonServices\Pages\ViewSalonService;
use App\Filament\Resources\Tenant\SalonServices\Schemas\SalonServiceForm;
use App\Filament\Resources\Tenant\SalonServices\Schemas\SalonServiceInfolist;
use App\Filament\Resources\Tenant\SalonServices\Tables\SalonServicesTable;
use App\Models\Tenant\SalonService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SalonServiceResource extends Resource
{
    protected static ?string $model = SalonService::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScissors;

    protected static string|UnitEnum|null $navigationGroup = 'إدارة الصالون';

    protected static ?string $navigationLabel = 'الخدمات';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'خدمة';

    protected static ?string $pluralModelLabel = 'الخدمات';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return SalonServiceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SalonServiceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SalonServicesTable::configure($table);
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
            'index' => ListSalonServices::route('/'),
            'create' => CreateSalonService::route('/create'),
            'view' => ViewSalonService::route('/{record}'),
            'edit' => EditSalonService::route('/{record}/edit'),
        ];
    }
}
