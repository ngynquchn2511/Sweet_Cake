<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BannerFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'image' => 'uploads/banners/' . fake()->uuid() . '.jpg',
            'link' => fake()->url(),
            // Khớp bộ giá trị vị trí mà BannerController validate (sau migration fix_banners_position_enum)
            'position' => fake()->randomElement(['homepage_main', 'homepage_sub', 'sidebar', 'footer']),
            'display_order' => fake()->numberBetween(0, 20),
            'status' => 'active',
            'start_date' => null,
            'end_date' => null,
        ];
    }
}
