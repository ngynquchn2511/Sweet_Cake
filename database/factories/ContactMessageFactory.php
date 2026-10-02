<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ContactMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'subject' => fake()->regexify('[A-Za-z0-9]{200}'),
            'message' => fake()->text(),
            'status' => fake()->randomElement(["new","read","replied"]),
            'admin_reply' => fake()->text(),
            'replied_at' => fake()->dateTime(),
        ];
    }
}
