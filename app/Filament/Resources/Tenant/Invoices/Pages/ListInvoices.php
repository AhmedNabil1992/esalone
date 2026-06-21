<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tenant\Invoices\Pages;

use App\Filament\Resources\Tenant\Invoices\InvoiceResource;
use Filament\Resources\Pages\ListRecords;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
