<?php

namespace App\Filament\Resources\Tenant\CustomerLevels;

use App\Filament\Resources\Tenant\CustomerLevels\Pages\CreateCustomerLevel;
use App\Filament\Resources\Tenant\CustomerLevels\Pages\EditCustomerLevel;
use App\Filament\Resources\Tenant\CustomerLevels\Pages\ListCustomerLevels;
use App\Filament\Resources\Tenant\CustomerLevels\Schemas\CustomerLevelForm;
use App\Filament\Resources\Tenant\CustomerLevels\Tables\CustomerLevelsTable;
use App\Models\Tenant\CustomerLevel;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class CustomerLevelResource extends Resource
{
    protected static ?string $model = CustomerLevel::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string|UnitEnum|null $navigationGroup = 'العملاء';

    protected static ?string $navigationLabel = 'فئات ومستويات العملاء';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'فئات ومستويات العملاء';

    protected static ?string $pluralModelLabel = 'فئات ومستويات العملاء';

    public static function form(Schema $schema): Schema
    {
        return CustomerLevelForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomerLevelsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomerLevels::route('/'),
            'create' => CreateCustomerLevel::route('/create'),
            'edit' => EditCustomerLevel::route('/{record}/edit'),
        ];
    }
}
