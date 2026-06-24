<?php

namespace App\Filament\Resources\Tenant\Shifts\Shifts;

use App\Filament\Resources\Tenant\Shifts\Shifts\Pages\CreateShift;
use App\Filament\Resources\Tenant\Shifts\Shifts\Pages\EditShift;
use App\Filament\Resources\Tenant\Shifts\Shifts\Pages\ListShifts;
use App\Filament\Resources\Tenant\Shifts\Shifts\Schemas\ShiftForm;
use App\Filament\Resources\Tenant\Shifts\Shifts\Schemas\ShiftInfolist;
use App\Filament\Resources\Tenant\Shifts\Shifts\Tables\ShiftsTable;
use App\Models\Tenant\Shift;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class ShiftResource extends Resource
{
    protected static ?string $model = Shift::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string|UnitEnum|null $navigationGroup = 'الشيفتات';

    protected static ?string $navigationLabel = 'إدارة الشيفتات';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'شيفت';

    protected static ?string $pluralModelLabel = 'الشيفتات';

    public static function form(Schema $schema): Schema
    {
        return ShiftForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ShiftInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ShiftsTable::configure($table);
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
            'index' => ListShifts::route('/'),
            'create' => CreateShift::route('/create'),
            'edit' => EditShift::route('/{record}/edit'),
        ];
    }
}
