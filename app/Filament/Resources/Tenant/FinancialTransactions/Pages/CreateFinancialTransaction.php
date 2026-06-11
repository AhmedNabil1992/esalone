<?php

namespace App\Filament\Resources\Tenant\FinancialTransactions\Pages;

use App\Filament\Resources\Tenant\FinancialTransactions\FinancialTransactionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFinancialTransaction extends CreateRecord
{
    protected static string $resource = FinancialTransactionResource::class;
}
