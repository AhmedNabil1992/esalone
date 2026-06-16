<?php

namespace App\Filament\Resources\Tenant\Purchases\RelationManagers;

use App\Filament\Resources\Tenant\PurchaseItems\Schemas\PurchaseItemForm;
use App\Filament\Resources\Tenant\PurchaseItems\Tables\PurchaseItemsTable;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class PurchaseItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'بنود المشتريات';

    public function form(Schema $schema): Schema
    {
        return PurchaseItemForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return PurchaseItemsTable::configure($table)
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
