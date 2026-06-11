<?php

namespace App\Filament\Resources\Tenant\PlayTypes;

use App\Filament\Resources\Tenant\PlayTypes\Pages\CreatePlayType;
use App\Filament\Resources\Tenant\PlayTypes\Pages\EditPlayType;
use App\Filament\Resources\Tenant\PlayTypes\Pages\ListPlayTypes;
use App\Filament\Resources\Tenant\PlayTypes\Pages\ViewPlayType;
use App\Filament\Resources\Tenant\PlayTypes\Schemas\PlayTypeForm;
use App\Filament\Resources\Tenant\PlayTypes\Schemas\PlayTypeInfolist;
use App\Filament\Resources\Tenant\PlayTypes\Tables\PlayTypesTable;
use App\Models\Tenant\PlayType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class PlayTypeResource extends Resource
{
    protected static ?string $model = PlayType::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-video-camera';

    protected static string|UnitEnum|null $navigationGroup = 'إدارة الأجهزة';

    protected static ?string $navigationLabel = 'أنواع اللعب';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'نوع لعب';

    protected static ?string $pluralModelLabel = 'أنواع اللعب';

    public static function form(Schema $schema): Schema
    {
        return PlayTypeForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PlayTypeInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PlayTypesTable::configure($table);
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
            'index' => ListPlayTypes::route('/'),
            'create' => CreatePlayType::route('/create'),
            'view' => ViewPlayType::route('/{record}'),
            'edit' => EditPlayType::route('/{record}/edit'),
        ];
    }
}
