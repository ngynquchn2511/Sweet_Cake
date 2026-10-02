<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'username' => 'admin',
            'full_name' => 'Admin Sweet Cake',
            'email' => 'admin@sweetcake.test',
            'phone' => '0900000001',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        User::factory()->create([
            'username' => 'customer',
            'full_name' => 'Customer Sweet Cake',
            'email' => 'customer@sweetcake.test',
            'phone' => '0900000002',
            'password' => Hash::make('customer123'),
            'role' => 'customer',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $categories = [
            ['name' => 'Bánh mì', 'slug' => 'banh-mi', 'description' => 'Bánh mì thơm ngon mỗi ngày.', 'image' => 'uploads/categories/1770124827_6981f61ba0edd.jpg'],
            ['name' => 'Bánh ngọt', 'slug' => 'banh-ngot', 'description' => 'Các loại bánh ngọt hấp dẫn.', 'image' => 'uploads/categories/1770124971_6981f6ab75ce0.jpg'],
            ['name' => 'Bánh kem', 'slug' => 'banh-kem', 'description' => 'Bánh kem cho những dịp đặc biệt.', 'image' => 'uploads/categories/1769900670_697e8a7e28ac9.jpg'],
            ['name' => 'Bánh tart', 'slug' => 'banh-tart', 'description' => 'Bánh tart với lớp nhân mềm mịn.', 'image' => 'uploads/categories/1770125143_6981f757a5589.jpg'],
            ['name' => 'Bánh mousse', 'slug' => 'banh-mousse', 'description' => 'Bánh mousse mát lạnh, nhẹ xốp.', 'image' => 'uploads/categories/1770125403_6981f85b5b473.jpg'],
            ['name' => 'Bánh cupcake', 'slug' => 'banh-cupcake', 'description' => 'Cupcake nhỏ xinh, nhiều hương vị.', 'image' => 'uploads/categories/1770156221_698270bd91719.jpg'],
            ['name' => 'Bánh quy', 'slug' => 'banh-quy', 'description' => 'Bánh quy giòn thơm dùng cùng trà.', 'image' => 'uploads/categories/1770156384_6982716061112.jpg'],
            ['name' => 'Bánh su kem', 'slug' => 'banh-su-kem', 'description' => 'Bánh su kem béo mịn.', 'image' => 'uploads/categories/1770156664_69827278af1ba.jpg'],
            ['name' => 'Bánh croissant', 'slug' => 'banh-croissant', 'description' => 'Croissant vàng giòn, thơm bơ.', 'image' => 'uploads/categories/1770156853_6982733528a5e.jpg'],
            ['name' => 'Bánh macaron', 'slug' => 'banh-macaron', 'description' => 'Macaron nhiều màu sắc và hương vị.', 'image' => 'uploads/categories/1770157013_698273d5965f1.jpg'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(['slug' => $category['slug']], array_merge($category, [
                'status' => 'active',
            ]));
        }

        \App\Models\Product::factory(20)->create([
            'status' => 'active',
        ]);

        $this->call(DashboardDemoSeeder::class);
    }
}
