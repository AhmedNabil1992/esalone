<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Actions\Tenant\ReversePurchaseAction;
use Tests\TestCase;

class ReversePurchaseActionTest extends TestCase
{
    public function test_can_reverse_only_when_status_is_posted(): void
    {
        $this->assertTrue(ReversePurchaseAction::canReverse('posted'));
        $this->assertFalse(ReversePurchaseAction::canReverse('reversed'));
        $this->assertFalse(ReversePurchaseAction::canReverse('draft'));
    }
}
