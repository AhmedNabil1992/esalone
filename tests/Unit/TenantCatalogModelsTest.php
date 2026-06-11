<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Tenant\CatalogItem;
use App\Models\Tenant\InventoryGroup;
use App\Models\Tenant\ProductComponent;
use Tests\TestCase;

class TenantCatalogModelsTest extends TestCase
{
    public function test_catalog_item_marks_product_and_component_states(): void
    {
        $product = CatalogItem::factory()->product()->make();
        $component = CatalogItem::factory()->component()->make();

        $this->assertTrue($product->is_product);
        $this->assertFalse($product->is_component);

        $this->assertFalse($component->is_product);
        $this->assertTrue($component->is_component);
    }

    public function test_inventory_group_and_catalog_item_support_image_fields(): void
    {
        $group = InventoryGroup::factory()->make([
            'picture' => 'groups/example.png',
        ]);

        $item = CatalogItem::factory()->make([
            'picture' => 'items/example.png',
            'purchase_price' => '50.00',
            'sale_price' => '80.00',
        ]);

        $this->assertSame('groups/example.png', $group->picture);
        $this->assertSame('items/example.png', $item->picture);
        $this->assertSame('50.00', $item->purchase_price);
        $this->assertSame('80.00', $item->sale_price);
    }

    public function test_product_component_factory_uses_product_and_component_items(): void
    {
        $component = new ProductComponent([
            'product_id' => 10,
            'component_id' => 11,
            'quantity' => '2.500',
            'notes' => 'BOM line',
            'sort_order' => 3,
        ]);

        $this->assertSame(10, $component->product_id);
        $this->assertSame(11, $component->component_id);
        $this->assertSame('2.500', $component->quantity);
        $this->assertSame('BOM line', $component->notes);
        $this->assertSame(3, $component->sort_order);
    }
}
