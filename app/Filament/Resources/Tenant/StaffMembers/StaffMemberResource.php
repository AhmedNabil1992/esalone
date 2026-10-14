<?php

namespace App\Filament\Resources\Tenant\StaffMembers;

use App\Filament\Resources\Tenant\StaffMembers\Pages\CreateStaffMember;
use App\Filament\Resources\Tenant\StaffMembers\Pages\EditStaffMember;
use App\Filament\Resources\Tenant\StaffMembers\Pages\ListStaffMembers;
use App\Filament\Resources\Tenant\StaffMembers\Pages\ViewStaffMember;
use App\Filament\Resources\Tenant\StaffMembers\Schemas\StaffMemberForm;
use App\Filament\Resources\Tenant\StaffMembers\Schemas\StaffMemberInfolist;
use App\Filament\Resources\Tenant\StaffMembers\Tables\StaffMembersTable;
use App\Models\Tenant\StaffMember;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class StaffMemberResource extends Resource
{
    protected static ?string $model = StaffMember::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'إدارة الصالون';

    protected static ?string $navigationLabel = 'فريق العمل';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'موظف';

    protected static ?string $pluralModelLabel = 'فريق العمل';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return StaffMemberForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return StaffMemberInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StaffMembersTable::configure($table);
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
            'index' => ListStaffMembers::route('/'),
            'create' => CreateStaffMember::route('/create'),
            'view' => ViewStaffMember::route('/{record}'),
            'edit' => EditStaffMember::route('/{record}/edit'),
        ];
    }
}
