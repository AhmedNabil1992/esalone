<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Filament\Resources\Tenant\CashBoxLedgerEntries\CashBoxLedgerEntryResource;
use App\Filament\Resources\Tenant\CashBoxLedgerEntries\Pages\ListCashBoxLedgerEntries;
use App\Filament\Resources\Tenant\CashBoxLedgerEntries\Pages\ViewCashBoxLedgerEntry;
use App\Models\Tenant\CashBoxLedgerEntry;
use Tests\TestCase;

class CashBoxLedgerEntryResourceTest extends TestCase
{
    public function test_cash_box_ledger_entry_resource_registers_model_and_pages(): void
    {
        $this->assertSame(CashBoxLedgerEntry::class, CashBoxLedgerEntryResource::getModel());

        $pages = CashBoxLedgerEntryResource::getPages();

        $this->assertArrayHasKey('index', $pages);
        $this->assertArrayHasKey('view', $pages);
        $this->assertSame(ListCashBoxLedgerEntries::class, $pages['index']->getPage());
        $this->assertSame(ViewCashBoxLedgerEntry::class, $pages['view']->getPage());
    }
}
