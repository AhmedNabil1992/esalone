<?php

namespace App\Filament\Resources\Tenant\Reservations;

use App\Filament\Resources\Tenant\Reservations\Pages\CreateReservations;
use App\Filament\Resources\Tenant\Reservations\Pages\EditReservations;
use App\Filament\Resources\Tenant\Reservations\Pages\ListReservations;
use App\Filament\Resources\Tenant\Reservations\Schemas\ReservationsForm;
use App\Filament\Resources\Tenant\Reservations\Tables\ReservationsTable;
use App\Models\Tenant\Reservation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class ReservationsResource extends Resource
{
    protected static ?string $model = Reservation::class;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar';

    protected static string|UnitEnum|null $navigationGroup = 'اللعب';

    protected static ?string $navigationLabel = 'الحجوزات';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'الحجوزات';

    protected static ?string $pluralModelLabel = 'الحجوزات';

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return ReservationsForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReservationsTable::configure($table);
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
            'index' => ListReservations::route('/'),
            'create' => CreateReservations::route('/create'),
            'edit' => EditReservations::route('/{record}/edit'),
        ];
    }
}
