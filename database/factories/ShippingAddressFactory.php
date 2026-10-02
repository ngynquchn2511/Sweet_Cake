<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShippingAddressFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'receiver_name' => fake()->name(),
            'phone' => fake()->numerify('09########'),
            'province' => fake()->city(),
            'district' => fake()->citySuffix() . ' ' . fake()->numberBetween(1, 12),
            'ward' => 'Phường ' . fake()->numberBetween(1, 20),
            'address' => fake()->text(),
            'note' => fake()->text(),
            'is_default' => fake()->boolean(),
        ];
    }
}
