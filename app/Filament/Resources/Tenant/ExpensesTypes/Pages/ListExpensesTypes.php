<?php

namespace App\Filament\Resources\Tenant\ExpensesTypes\Pages;

use App\Filament\Resources\Tenant\ExpensesTypes\ExpensesTypesResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExpensesTypes extends ListRecords
{
    protected static string $resource = ExpensesTypesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('إضافة نوع مصروفات جديد'),
        ];
    }
}
