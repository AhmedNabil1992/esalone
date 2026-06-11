<?php

namespace App\Filament\Resources\Tenant\FinancialTransactions\Pages;

use App\Filament\Resources\Tenant\FinancialTransactions\FinancialTransactionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewFinancialTransaction extends ViewRecord
{
    protected static string $resource = FinancialTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
