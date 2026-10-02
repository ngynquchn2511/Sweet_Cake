<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $catalog = [
            ['name' => 'Bánh Kem Matcha', 'image' => 'uploads/products/banh-kem-matcha.jpg'],
            ['name' => 'Bánh Kem Socola', 'image' => 'uploads/products/banh-kem-socola.jpg'],
            ['name' => 'Bánh Kem Tiramisu', 'image' => 'uploads/products/banh-kem-tiramisu.jpg'],
            ['name' => 'Bánh Kem Trái Cây', 'image' => 'uploads/products/banh-kem-trai-cay.jpg'],
            ['name' => 'Bánh Mì', 'image' => 'uploads/products/banh-mi.jpg'],
            ['name' => 'Bánh Mì Hạnh Nhân', 'image' => 'uploads/products/banh-mi-hanh-nhan.jpg'],
            ['name' => 'Bánh Mì Phô Mai', 'image' => 'uploads/products/banh-mi-pho-mai.jpg'],
            ['name' => 'Bánh Croissant', 'image' => 'uploads/products/Banh-Croissant.jpg'],
            ['name' => 'Bánh Cupcake', 'image' => 'uploads/products/Banh-Cupcake.jpg'],
            ['name' => 'Bánh Macaron', 'image' => 'uploads/products/Banh-Macaron.jpg'],
            ['name' => 'Bánh Quy Bơ', 'image' => 'uploads/products/banh-quy-bo.jpg'],
            ['name' => 'Bánh Quy Matcha', 'image' => 'uploads/products/banh-quy-matcha.jpg'],
            ['name' => 'Bánh Quy Phô Mai', 'image' => 'uploads/products/banh-quy-pho-mai.jpg'],
            ['name' => 'Bánh Pía', 'image' => 'uploads/products/banh-pia.jpg'],
            ['name' => 'Bánh Su Kem', 'image' => 'uploads/products/banh-su-kem.jpg'],
            ['name' => 'Bánh Tart Chocolate', 'image' => 'uploads/products/tart-chocolate.jpg'],
            ['name' => 'Bánh Tart Dâu', 'image' => 'uploads/products/tart-dau.jpg'],
            ['name' => 'Mousse Xoài', 'image' => 'uploads/products/mousse-xoai.jpg'],
            ['name' => 'Panna Cotta', 'image' => 'uploads/products/panna-cotta.jpg'],
            ['name' => 'Flan Caramen', 'image' => 'uploads/products/flan-caramen.jpg'],
        ];

        $selected = Arr::random($catalog);

        return [
            'category_id' => Category::query()->inRandomOrder()->value('id') ?? Category::factory(),
            'name' => $selected['name'],
            'slug' => Str::slug($selected['name']) . '-' . $this->faker->unique()->numerify('###'),
            'price' => fake()->randomFloat(2, 50000, 999000),
            'discount_price' => fake()->randomFloat(2, 35000, 750000),
            'description' => fake()->sentence(12),
            'image' => $selected['image'],
            'images' => json_encode([$selected['image']]),
            'stock' => fake()->numberBetween(10, 200),
            'unit' => 'chiếc',
            'weight' => fake()->randomFloat(2, 0.2, 2.5),
            'status' => 'active',
            'view_count' => fake()->numberBetween(0, 5000),
            'sold_count' => fake()->numberBetween(0, 1000),
        ];
    }
}
