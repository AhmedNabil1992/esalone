<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Filament\Resources\Tenant\Invoices\InvoiceResource;
use App\Filament\Resources\Tenant\Invoices\RelationManagers\InvoiceItemsRelationManager;
use App\Filament\Resources\Tenant\Invoices\RelationManagers\InvoicePaymentsRelationManager;
use App\Filament\Resources\Tenant\Invoices\RelationManagers\PlaySessionDetailsRelationManager;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\MarketHistory;
use Tests\TestCase;

class InvoiceResourceTest extends TestCase
{
    public function test_invoice_resource_registers_expected_relation_managers(): void
    {
        $relations = InvoiceResource::getRelations();

        $this->assertContains(InvoicePaymentsRelationManager::class, $relations);
        $this->assertContains(PlaySessionDetailsRelationManager::class, $relations);
        $this->assertContains(InvoiceItemsRelationManager::class, $relations);
    }

    public function test_invoice_has_market_history_relation_for_direct_sales(): void
    {
        $relation = (new Invoice)->marketHistories();

        $this->assertSame(MarketHistory::class, $relation->getRelated()::class);
        $this->assertSame('invoice_no', $relation->getForeignKeyName());
        $this->assertSame('invoice_no', $relation->getLocalKeyName());
    }
}
