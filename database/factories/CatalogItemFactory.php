<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tenant\CatalogItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CatalogItem>
 */
class CatalogItemFactory extends Factory
{
    protected $model = CatalogItem::class;

    public function definition(): array
    {
        return [
            'inventory_group_id' => null,
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->sentence(),
            'picture' => null,
            'purchase_price' => fake()->randomFloat(2, 5, 300),
            'sale_price' => fake()->randomFloat(2, 10, 500),
            'is_product' => false,
            'is_component' => false,
            'is_active' => true,
        ];
    }

    public function product(): static
    {
        return $this->state(fn (): array => [
            'is_product' => true,
            'is_component' => false,
        ]);
    }

    public function component(): static
    {
        return $this->state(fn (): array => [
            'is_product' => false,
            'is_component' => true,
        ]);
    }
}
