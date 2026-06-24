<?php

namespace App\Filament\Resources\Tenant\PlaySessions;

use App\Filament\Resources\Tenant\PlaySessions\Pages\CreatePlaySession;
use App\Filament\Resources\Tenant\PlaySessions\Pages\EditPlaySession;
use App\Filament\Resources\Tenant\PlaySessions\Pages\ListPlaySessions;
use App\Filament\Resources\Tenant\PlaySessions\Pages\ViewPlaySession;
use App\Filament\Resources\Tenant\PlaySessions\RelationManagers\OrderSessionDetailsRelationManager;
use App\Filament\Resources\Tenant\PlaySessions\RelationManagers\PlaySessionDetailsRelationManager;
use App\Filament\Resources\Tenant\PlaySessions\Schemas\PlaySessionForm;
use App\Filament\Resources\Tenant\PlaySessions\Schemas\PlaySessionInfolist;
use App\Filament\Resources\Tenant\PlaySessions\Tables\PlaySessionsTable;
use App\Models\Tenant\PlaySessions;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class PlaySessionResource extends Resource
{
    protected static ?string $model = PlaySessions::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static string|UnitEnum|null $navigationGroup = 'Play';

    protected static ?string $navigationLabel = 'جلسات اللعب';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'جلسة لعب';

    protected static ?string $pluralModelLabel = 'جلسات اللعب';

    public static function form(Schema $schema): Schema
    {
        return PlaySessionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PlaySessionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PlaySessionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            PlaySessionDetailsRelationManager::class,
            OrderSessionDetailsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlaySessions::route('/'),
            // 'create' => CreatePlaySession::route('/create'),
            'view' => ViewPlaySession::route('/{record}'),
            // 'edit' => EditPlaySession::route('/{record}/edit'),
        ];
    }
}
