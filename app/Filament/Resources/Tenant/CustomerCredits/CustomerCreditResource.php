<?php

namespace App\Filament\Resources\Tenant\CustomerCredits;

use App\Filament\Resources\Tenant\CustomerCredits\Pages\CreateCustomerCredit;
use App\Filament\Resources\Tenant\CustomerCredits\Pages\ListCustomerCredits;
use App\Filament\Resources\Tenant\CustomerCredits\Pages\ViewCustomerCredit;
use App\Filament\Resources\Tenant\CustomerCredits\Schemas\CustomerCreditForm;
use App\Filament\Resources\Tenant\CustomerCredits\Schemas\CustomerCreditInfolist;
use App\Filament\Resources\Tenant\CustomerCredits\Tables\CustomerCreditsTable;
use App\Models\Tenant\CustomerCredit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class CustomerCreditResource extends Resource
{
    protected static ?string $model = CustomerCredit::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|UnitEnum|null $navigationGroup = 'العملاء';

    protected static ?string $navigationLabel = 'حركات الرصيد';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'حركات الرصيد';

    protected static ?string $pluralModelLabel = 'حركات الرصيد';

    public static function form(Schema $schema): Schema
    {
        return CustomerCreditForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CustomerCreditInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomerCreditsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomerCredits::route('/'),
            'create' => CreateCustomerCredit::route('/create'),
            'view' => ViewCustomerCredit::route('/{record}'),
        ];
    }
}
