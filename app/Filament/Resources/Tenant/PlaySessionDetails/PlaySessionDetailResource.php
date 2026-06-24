<?php

namespace App\Filament\Resources\Tenant\PlaySessionDetails;

use App\Filament\Resources\Tenant\PlaySessionDetails\Pages\ListPlaySessionDetails;
use App\Filament\Resources\Tenant\PlaySessionDetails\Pages\ViewPlaySessionDetail;
use App\Filament\Resources\Tenant\PlaySessionDetails\Schemas\PlaySessionDetailForm;
use App\Filament\Resources\Tenant\PlaySessionDetails\Schemas\PlaySessionDetailInfolist;
use App\Filament\Resources\Tenant\PlaySessionDetails\Tables\PlaySessionDetailsTable;
use App\Models\Tenant\PlaySessionDetail;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class PlaySessionDetailResource extends Resource
{
    protected static ?string $model = PlaySessionDetail::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-list-bullet';

    protected static string|UnitEnum|null $navigationGroup = 'Play';

    protected static ?string $navigationLabel = 'تقرير اللعب الحالي';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'تقرير اللعب الحالي';

    protected static ?string $pluralModelLabel = 'تقرير اللعب الحالي';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->orderByDesc('play_session_id');
    }

    public static function form(Schema $schema): Schema
    {
        return PlaySessionDetailForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PlaySessionDetailInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PlaySessionDetailsTable::configure($table);
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
            'index' => ListPlaySessionDetails::route('/'),
            'view' => ViewPlaySessionDetail::route('/{record}'),
        ];
    }
}
