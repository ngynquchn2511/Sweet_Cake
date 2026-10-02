<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'payment_method' => fake()->randomElement(["cod","bank_transfer","momo","vnpay","zalopay"]),
            'transaction_id' => fake()->regexify('[A-Za-z0-9]{100}'),
            'amount' => fake()->randomFloat(2, 0, 99999999.99),
            'payment_status' => fake()->randomElement(["pending","success","failed","refunded"]),
            'paid_at' => fake()->dateTime(),
            'note' => fake()->text(),
        ];
    }
}
