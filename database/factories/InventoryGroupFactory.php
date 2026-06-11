<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tenant\InventoryGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryGroup>
 */
class InventoryGroupFactory extends Factory
{
    protected $model = InventoryGroup::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'description' => fake()->sentence(),
            'picture' => null,
            'is_active' => true,
        ];
    }
}
