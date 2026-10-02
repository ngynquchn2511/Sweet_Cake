<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\ContactMessage;
use App\Models\Message;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Review;
use App\Models\ShippingAddress;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Dữ liệu minh hoạ cho buổi demo: mã khuyến mãi, banner các vị trí, đánh giá, tin nhắn/liên hệ.
 * Chạy lại nhiều lần không bị nhân đôi dữ liệu.
 *   php artisan db:seed --class=DemoDataSeeder
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $customer = User::where('email', 'customer@sweetcake.test')->first();
        $products = Product::where('status', 'active')->orderBy('id')->take(3)->get();

        Promotion::updateOrCreate(['code' => 'SWEET10'], [
            'description' => 'Giảm 10% cho đơn từ 100.000đ (tối đa 50.000đ)',
            'discount_type' => 'percent',
            'discount_value' => 10,
            'min_order_value' => 100000,
            'max_discount' => 50000,
            'usage_limit' => 100,
            'used_count' => 0,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonths(3),
            'status' => 'active',
        ]);

        Promotion::updateOrCreate(['code' => 'GIAM30K'], [
            'description' => 'Giảm thẳng 30.000đ',
            'discount_type' => 'fixed',
            'discount_value' => 30000,
            'min_order_value' => 0,
            'usage_limit' => null,
            'used_count' => 0,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonths(3),
            'status' => 'active',
        ]);

        $banners = [
            ['title' => 'Bánh ngọt mỗi ngày', 'image' => 'images/slider_1.webp', 'position' => 'homepage_main', 'display_order' => 1],
            ['title' => 'Ưu đãi mùa thu', 'image' => 'images/slider_2.webp', 'position' => 'homepage_main', 'display_order' => 2],
            ['title' => 'Nhập mã SWEET10 giảm 10%', 'image' => 'uploads/products/Banh-Croissant.jpg', 'position' => 'homepage_sub', 'display_order' => 1],
            ['title' => 'Cupcake mới ra lò', 'image' => 'uploads/products/Banh-Cupcake.jpg', 'position' => 'sidebar', 'display_order' => 1],
        ];
        foreach ($banners as $banner) {
            Banner::updateOrCreate(['title' => $banner['title']], $banner + ['link' => '/products', 'status' => 'active']);
        }

        if ($customer) {
            ShippingAddress::firstOrCreate(['user_id' => $customer->id, 'receiver_name' => 'Customer Sweet Cake'], [
                'phone' => '0900000002',
                'province' => 'Hà Nội',
                'district' => 'Cầu Giấy',
                'ward' => 'Dịch Vọng Hậu',
                'address' => '144 Xuân Thủy',
                'is_default' => true,
            ]);

            $comments = [
                [5, 'Bánh mềm, thơm, giao hàng nhanh!', 'approved'],
                [4, 'Ngon, hơi ngọt một chút.', 'approved'],
                [2, 'Bánh bị móp khi nhận hàng.', 'pending'],
            ];
            foreach ($products as $i => $product) {
                [$rating, $comment, $status] = $comments[$i] ?? $comments[0];
                Review::updateOrCreate(
                    ['user_id' => $customer->id, 'product_id' => $product->id],
                    ['rating' => $rating, 'comment' => $comment, 'status' => $status, 'images' => []]
                );
            }

            Message::firstOrCreate(['user_id' => $customer->id, 'subject' => 'Shop có bánh không đường không?'], [
                'content' => 'Mình cần bánh cho người ăn kiêng, shop có loại nào không?',
                'status' => 'unread',
            ]);
        }

        ContactMessage::firstOrCreate(['email' => 'khachle@example.com', 'subject' => 'Đặt bánh sinh nhật số lượng lớn'], [
            'name' => 'Trần Thị Lan',
            'phone' => '0912345678',
            'message' => 'Mình muốn đặt 30 bánh cupcake cho tiệc sinh nhật cuối tuần này.',
            'status' => 'new',
        ]);
    }
}
