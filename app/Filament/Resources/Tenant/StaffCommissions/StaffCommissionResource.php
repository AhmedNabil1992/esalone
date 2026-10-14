<?php

namespace App\Filament\Resources\Tenant\StaffCommissions;

use App\Filament\Resources\Tenant\StaffCommissions\Pages\ListStaffCommissions;
use App\Filament\Resources\Tenant\StaffCommissions\Tables\StaffCommissionsTable;
use App\Models\Tenant\StaffCommission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class StaffCommissionResource extends Resource
{
    protected static ?string $model = StaffCommission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'إدارة الصالون';

    protected static ?string $navigationLabel = 'عمولات الموظفين';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'عمولة';

    protected static ?string $pluralModelLabel = 'عمولات الموظفين';

    public static function table(Table $table): Table
    {
        return StaffCommissionsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['appointment', 'staffMember'])
            ->withSum('payouts', 'amount');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStaffCommissions::route('/'),
        ];
    }
}
