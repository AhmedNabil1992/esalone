<?php

namespace App\Filament\Resources\Tenant\ExpenseHistories\Pages;

use App\Filament\Resources\Tenant\ExpenseHistories\ExpenseHistoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExpenseHistories extends ListRecords
{
    protected static string $resource = ExpenseHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
