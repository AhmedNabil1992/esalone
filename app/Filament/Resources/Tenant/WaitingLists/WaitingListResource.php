<?php

namespace App\Filament\Resources\Tenant\WaitingLists;

use App\Filament\Resources\Tenant\WaitingLists\Pages\CreateWaitingList;
use App\Filament\Resources\Tenant\WaitingLists\Pages\EditWaitingList;
use App\Filament\Resources\Tenant\WaitingLists\Pages\ListWaitingLists;
use App\Filament\Resources\Tenant\WaitingLists\Schemas\WaitingListForm;
use App\Filament\Resources\Tenant\WaitingLists\Tables\WaitingListsTable;
use App\Models\Tenant\WaitingList;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class WaitingListResource extends Resource
{
    protected static ?string $model = WaitingList::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-queue-list';

    protected static string|UnitEnum|null $navigationGroup = 'اللعب';

    protected static ?string $navigationLabel = 'قائمة الانتظار';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'عميل منتظر';

    protected static ?string $pluralModelLabel = 'قائمة الانتظار';

    public static function form(Schema $schema): Schema
    {
        return WaitingListForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WaitingListsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWaitingLists::route('/'),
            'create' => CreateWaitingList::route('/create'),
            'edit' => EditWaitingList::route('/{record}/edit'),
        ];
    }
}
