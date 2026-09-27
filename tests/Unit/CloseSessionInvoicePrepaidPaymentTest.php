<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Actions\Tenant\CloseSessionInvoiceAction;
use App\Models\Tenant\CashBox;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class CloseSessionInvoicePrepaidPaymentTest extends TestCase
{
    #[Test]
    public function it_adds_the_prepaid_amount_to_the_selected_cash_box_payment(): void
    {
        $cashBox = new CashBox;
        $cashBox->id = 7;

        $allocations = [[
            'cash_box' => $cashBox,
            'cash_box_id' => 7,
            'amount' => 60.00,
        ]];

        $result = $this->invokeAddPrepaidAmountToAllocations($allocations, 40.00, $cashBox);

        $this->assertCount(1, $result);
        $this->assertSame(7, $result[0]['cash_box_id']);
        $this->assertSame(100.00, $result[0]['amount']);
    }

    #[Test]
    public function it_records_a_fully_prepaid_invoice_against_the_selected_cash_box(): void
    {
        $cashBox = new CashBox;
        $cashBox->id = 9;

        $result = $this->invokeAddPrepaidAmountToAllocations([], 100.00, $cashBox);

        $this->assertCount(1, $result);
        $this->assertSame(9, $result[0]['cash_box_id']);
        $this->assertSame(100.00, $result[0]['amount']);
    }

    /**
     * @param  array<int,array{cash_box:CashBox,cash_box_id:int,amount:float}>  $allocations
     * @return array<int,array{cash_box:CashBox,cash_box_id:int,amount:float}>
     */
    private function invokeAddPrepaidAmountToAllocations(array $allocations, float $prepaidAmount, CashBox $cashBox): array
    {
        $method = new ReflectionMethod(CloseSessionInvoiceAction::class, 'addPrepaidAmountToAllocations');

        return $method->invoke(new CloseSessionInvoiceAction, $allocations, $prepaidAmount, $cashBox);
    }
}
