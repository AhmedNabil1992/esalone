<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tenant\CatalogItem;
use App\Models\Tenant\ProductComponent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductComponent>
 */
class ProductComponentFactory extends Factory
{
    protected $model = ProductComponent::class;

    public function definition(): array
    {
        return [
            'product_id' => CatalogItem::factory()->product(),
            'component_id' => CatalogItem::factory()->component(),
            'quantity' => fake()->randomFloat(3, 1, 10),
            'notes' => fake()->optional()->sentence(),
            'sort_order' => 0,
        ];
    }
}
