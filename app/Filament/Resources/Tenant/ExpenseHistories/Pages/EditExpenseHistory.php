<?php

namespace App\Filament\Resources\Tenant\ExpenseHistories\Pages;

use App\Filament\Resources\Tenant\ExpenseHistories\ExpenseHistoryResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditExpenseHistory extends EditRecord
{
    protected static string $resource = ExpenseHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}
