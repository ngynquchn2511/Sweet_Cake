<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\ShippingAddress;
use App\Models\Promotion;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'shipping_address_id' => ShippingAddress::factory(),
            'promotion_id' => null,
            'order_code' => 'ORD-' . strtoupper(fake()->unique()->bothify('##??##??##')),
            'subtotal' => 200000,
            'shipping_fee' => 30000,
            'discount_amount' => 0,
            'total_amount' => 230000,
            'order_status' => 'pending',
            'payment_method' => 'cod',
            'payment_status' => 'unpaid',
            'note' => fake()->text(),
            'cancelled_reason' => null,
        ];
    }
}
