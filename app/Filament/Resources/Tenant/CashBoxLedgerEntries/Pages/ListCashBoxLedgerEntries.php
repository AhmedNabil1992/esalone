<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tenant\CashBoxLedgerEntries\Pages;

use App\Filament\Resources\Tenant\CashBoxLedgerEntries\CashBoxLedgerEntryResource;
use Filament\Resources\Pages\ListRecords;

class ListCashBoxLedgerEntries extends ListRecords
{
    protected static string $resource = CashBoxLedgerEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
