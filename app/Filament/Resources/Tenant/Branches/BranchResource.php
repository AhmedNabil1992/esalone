<?php

namespace App\Filament\Resources\Tenant\Branches;

use App\Filament\Resources\Tenant\Branches\Pages\CreateBranch;
use App\Filament\Resources\Tenant\Branches\Pages\EditBranch;
use App\Filament\Resources\Tenant\Branches\Pages\ListBranches;
use App\Filament\Resources\Tenant\Branches\Pages\ViewBranch;
use App\Filament\Resources\Tenant\Branches\Schemas\BranchForm;
use App\Filament\Resources\Tenant\Branches\Schemas\BranchInfolist;
use App\Filament\Resources\Tenant\Branches\Tables\BranchesTable;
use App\Models\Tenant\Branch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    protected static string|UnitEnum|null $navigationGroup = 'إعدادات النظام';

    protected static ?string $navigationLabel = 'الفروع';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'فرع';

    protected static ?string $pluralModelLabel = 'الفروع';

    public static function form(Schema $schema): Schema
    {
        return BranchForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BranchInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BranchesTable::configure($table);
    }

    public static function canCreate(): bool
    {
        $tenant = tenancy()->tenant ?? (function_exists('tenant') ? tenant() : null);
        if (! $tenant) {
            return true;
        }

        return $tenant->canCreateBranch(Branch::count());
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
            'index' => ListBranches::route('/'),
            'create' => CreateBranch::route('/create'),
            'view' => ViewBranch::route('/{record}'),
            'edit' => EditBranch::route('/{record}/edit'),
        ];
    }
}
