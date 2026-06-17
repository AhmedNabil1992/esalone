<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Tenant\CashBoxLedgerEntry;
use App\Models\Tenant\Purchase;
use Tests\TestCase;

class CashBoxLedgerEntryModelTest extends TestCase
{
    public function test_ledger_entry_supports_reference_fields_and_amount_cast(): void
    {
        $entry = new CashBoxLedgerEntry([
            'reference_type' => Purchase::class,
            'reference_id' => 7,
            'direction' => 'out',
            'operation' => 'purchase',
            'amount' => '1200.99',
        ]);

        $this->assertSame(Purchase::class, $entry->reference_type);
        $this->assertSame(7, $entry->reference_id);
        $this->assertSame('out', $entry->direction);
        $this->assertSame('purchase', $entry->operation);
        $this->assertSame('1200.99', $entry->amount);
    }
}
