<?php

namespace App\Filament\Resources\Tenant\Offers;

use App\Filament\Resources\Tenant\Offers\Pages\CreateOffer;
use App\Filament\Resources\Tenant\Offers\Pages\EditOffer;
use App\Filament\Resources\Tenant\Offers\Pages\ListOffers;
use App\Filament\Resources\Tenant\Offers\Pages\ViewOffer;
use App\Filament\Resources\Tenant\Offers\Schemas\OfferForm;
use App\Filament\Resources\Tenant\Offers\Schemas\OfferInfolist;
use App\Filament\Resources\Tenant\Offers\Tables\OffersTable;
use App\Models\Tenant\Offer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class OfferResource extends Resource
{
    protected static ?string $model = Offer::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-ticket';

    protected static string|UnitEnum|null $navigationGroup = 'إدارة الأجهزة';

    protected static ?string $navigationLabel = 'العروض';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'عرض';

    protected static ?string $pluralModelLabel = 'العروض';

    public static function form(Schema $schema): Schema
    {
        return OfferForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return OfferInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OffersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOffers::route('/'),
            'create' => CreateOffer::route('/create'),
            'view' => ViewOffer::route('/{record}'),
            'edit' => EditOffer::route('/{record}/edit'),
        ];
    }
}
