<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdminLogFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => fake()->randomElement(["create","update","delete","login","logout"]),
            'table_name' => fake()->regexify('[A-Za-z0-9]{50}'),
            'record_id' => fake()->word(),
            'old_value' => '{}',
            'new_value' => '{}',
            'ip_address' => fake()->regexify('[A-Za-z0-9]{45}'),
            'user_agent' => fake()->text(),
        ];
    }
}
