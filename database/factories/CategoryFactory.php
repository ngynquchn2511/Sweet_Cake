<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $categories = [
            ['name' => 'Bánh mì', 'slug' => 'banh-mi', 'image' => 'uploads/categories/1770124827_6981f61ba0edd.jpg'],
            ['name' => 'Bánh ngọt', 'slug' => 'banh-ngot', 'image' => 'uploads/categories/1770124971_6981f6ab75ce0.jpg'],
            ['name' => 'Bánh kem', 'slug' => 'banh-kem', 'image' => 'uploads/categories/1769900670_697e8a7e28ac9.jpg'],
            ['name' => 'Bánh tart', 'slug' => 'banh-tart', 'image' => 'uploads/categories/1770125143_6981f757a5589.jpg'],
            ['name' => 'Bánh mousse', 'slug' => 'banh-mousse', 'image' => 'uploads/categories/1770125403_6981f85b5b473.jpg'],
            ['name' => 'Bánh cupcake', 'slug' => 'banh-cupcake', 'image' => 'uploads/categories/1770156221_698270bd91719.jpg'],
            ['name' => 'Bánh quy', 'slug' => 'banh-quy', 'image' => 'uploads/categories/1770156384_6982716061112.jpg'],
            ['name' => 'Bánh su kem', 'slug' => 'banh-su-kem', 'image' => 'uploads/categories/1770156664_69827278af1ba.jpg'],
            ['name' => 'Bánh croissant', 'slug' => 'banh-croissant', 'image' => 'uploads/categories/1770156853_6982733528a5e.jpg'],
            ['name' => 'Bánh macaron', 'slug' => 'banh-macaron', 'image' => 'uploads/categories/1770157013_698273d5965f1.jpg'],
        ];

        $category = fake()->randomElement($categories);
        // Hậu tố ngẫu nhiên để tạo nhiều danh mục trong cùng 1 test không vỡ ràng buộc unique name/slug
        $suffix = fake()->unique()->numerify('###');

        return [
            ...$category,
            'name' => "{$category['name']} {$suffix}",
            'slug' => "{$category['slug']}-{$suffix}",
            'description' => "Các loại {$category['name']} thơm ngon của Sweet Cake.",
            'status' => 'active',
            'display_order' => fake()->numberBetween(1, 10),
        ];
    }
}
