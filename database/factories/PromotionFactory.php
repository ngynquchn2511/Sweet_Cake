<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PromotionFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('SALE####??')),
            'description' => fake()->sentence(),
            'discount_type' => 'percent',
            'discount_value' => 10,
            'min_order_value' => 0,
            'max_discount' => null,
            'usage_limit' => null,
            'used_count' => 0,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
            'status' => 'active',
        ];
    }
}
