<?php

namespace App\Filament\Resources\Tenant\Appointments;

use App\Filament\Resources\Tenant\Appointments\Pages\CreateAppointment;
use App\Filament\Resources\Tenant\Appointments\Pages\EditAppointment;
use App\Filament\Resources\Tenant\Appointments\Pages\ListAppointments;
use App\Filament\Resources\Tenant\Appointments\Pages\ViewAppointment;
use App\Filament\Resources\Tenant\Appointments\Schemas\AppointmentForm;
use App\Filament\Resources\Tenant\Appointments\Schemas\AppointmentInfolist;
use App\Filament\Resources\Tenant\Appointments\Tables\AppointmentsTable;
use App\Models\Tenant\Appointment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'إدارة الصالون';

    protected static ?string $navigationLabel = 'المواعيد';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'موعد';

    protected static ?string $pluralModelLabel = 'المواعيد';

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return AppointmentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AppointmentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AppointmentsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['customer', 'staffMember', 'items', 'invoice']);
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
            'index' => ListAppointments::route('/'),
            'create' => CreateAppointment::route('/create'),
            'view' => ViewAppointment::route('/{record}'),
            'edit' => EditAppointment::route('/{record}/edit'),
        ];
    }
}
