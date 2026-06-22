<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'is_active' => true,
            'auto_renew' => true,
        ];
    }

    public function expiringSoon(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'ended_at' => now()->addDay(),
            ];
        });
    }

    public function expired(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'ended_at' => now()->subDay(),
            ];
        });
    }
}
