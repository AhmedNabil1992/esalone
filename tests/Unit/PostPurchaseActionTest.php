<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Actions\Tenant\PostPurchaseAction;
use Tests\TestCase;

class PostPurchaseActionTest extends TestCase
{
    public function test_line_total_is_rounded_to_two_decimals(): void
    {
        $lineTotal = PostPurchaseAction::calculateLineTotal(2.555, 10.333);

        $this->assertSame(26.4, $lineTotal);
    }

    public function test_round_currency_rounds_half_up_to_two_decimals(): void
    {
        $rounded = PostPurchaseAction::roundCurrency(199.995);

        $this->assertSame(200.0, $rounded);
    }
}
