<?php

namespace App\Filament\Resources\Tenant\Reservations;

use App\Filament\Resources\Tenant\Reservations\Pages\CreateReservations;
use App\Filament\Resources\Tenant\Reservations\Pages\EditReservations;
use App\Filament\Resources\Tenant\Reservations\Pages\ListReservations;
use App\Filament\Resources\Tenant\Reservations\Schemas\ReservationsForm;
use App\Filament\Resources\Tenant\Reservations\Tables\ReservationsTable;
use App\Models\Reservations;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ReservationsResource extends Resource
{
    protected static ?string $model = Reservations::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Reservation';

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
