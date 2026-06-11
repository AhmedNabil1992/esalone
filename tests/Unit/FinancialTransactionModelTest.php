<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Tenant\FinancialTransaction;
use App\Models\Tenant\Purchase;
use Tests\TestCase;

class FinancialTransactionModelTest extends TestCase
{
    public function test_financial_transaction_supports_reference_fields_and_amount_cast(): void
    {
        $transaction = new FinancialTransaction([
            'reference_type' => Purchase::class,
            'reference_id' => 15,
            'direction' => 'out',
            'category' => 'purchase',
            'amount' => '250.50',
            'affects_profit' => false,
        ]);

        $this->assertSame(Purchase::class, $transaction->reference_type);
        $this->assertSame(15, $transaction->reference_id);
        $this->assertSame('out', $transaction->direction);
        $this->assertSame('purchase', $transaction->category);
        $this->assertSame('250.50', $transaction->amount);
        $this->assertFalse($transaction->affects_profit);
    }
}
