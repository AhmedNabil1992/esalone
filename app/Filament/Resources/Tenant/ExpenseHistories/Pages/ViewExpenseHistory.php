<?php

namespace App\Filament\Resources\Tenant\ExpenseHistories\Pages;

use App\Filament\Resources\Tenant\ExpenseHistories\ExpenseHistoryResource;
use Filament\Resources\Pages\ViewRecord;

class ViewExpenseHistory extends ViewRecord
{
    protected static string $resource = ExpenseHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
