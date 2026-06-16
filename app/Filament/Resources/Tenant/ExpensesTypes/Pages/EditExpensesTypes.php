<?php

namespace App\Filament\Resources\Tenant\ExpensesTypes\Pages;

use App\Filament\Resources\Tenant\ExpensesTypes\ExpensesTypesResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditExpensesTypes extends EditRecord
{
    protected static string $resource = ExpensesTypesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
