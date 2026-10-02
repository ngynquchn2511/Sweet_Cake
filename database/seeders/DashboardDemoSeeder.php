<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ShippingAddress;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DashboardDemoSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::query()->where('status', 'active')->get();

        if ($products->isEmpty()) {
            $this->command?->warn('Bo qua du lieu dashboard: chua co san pham active.');
            return;
        }

        $customers = collect();

        for ($index = 1; $index <= 30; $index++) {
            $customers->push(User::updateOrCreate(
                ['email' => "demo.customer{$index}@sweetcake.test"],
                [
                    'username' => "demo_customer_{$index}",
                    'full_name' => "Khach hang Demo {$index}",
                    'phone' => '091' . str_pad((string) $index, 7, '0', STR_PAD_LEFT),
                    'password' => Hash::make('customer123'),
                    'role' => 'customer',
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]
            ));
        }

        foreach ($customers as $index => $customer) {
            ShippingAddress::updateOrCreate(
                ['user_id' => $customer->id, 'address' => "So {$index} Duong Demo"],
                [
                    'receiver_name' => $customer->full_name,
                    'phone' => $customer->phone,
                    'province' => 'Thanh pho Ho Chi Minh',
                    'district' => 'Quan 1',
                    'ward' => 'Phuong Ben Nghe',
                    'note' => 'Du lieu demo dashboard',
                    'is_default' => true,
                ]
            );
        }

        Order::where('order_code', 'like', 'DEMO-%')->delete();

        $statuses = ['completed', 'completed', 'completed', 'processing', 'pending', 'shipping', 'cancelled'];
        $paymentMethods = ['cod', 'momo', 'bank_transfer'];

        for ($index = 0; $index < 42; $index++) {
            $customer = $customers[$index % $customers->count()];
            $address = ShippingAddress::where('user_id', $customer->id)->first();
            $status = $statuses[$index % count($statuses)];
            $createdAt = $index === 0
                ? now()->subHours(2)
                : Carbon::now()->subDays(($index * 9) % 360)->subHours($index % 12);
            $product = $products[$index % $products->count()];
            $quantity = ($index % 4) + 1;
            $price = (float) ($product->discount_price ?: $product->price);
            $subtotal = $price * $quantity;
            $shippingFee = 30000;
            $discount = $index % 5 === 0 ? 20000 : 0;

            $order = Order::create([
                'user_id' => $customer->id,
                'shipping_address_id' => $address?->id,
                'order_code' => 'DEMO-' . strtoupper(Str::random(12)),
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'discount_amount' => $discount,
                'total_amount' => $subtotal + $shippingFee - $discount,
                'order_status' => $status,
                'payment_method' => $paymentMethods[$index % count($paymentMethods)],
                'payment_status' => $status === 'completed' ? 'paid' : 'unpaid',
                'note' => 'Don hang demo de kiem tra dashboard',
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => (string) $quantity,
                'price' => $price,
                'discount_price' => $product->discount_price,
                'subtotal' => $subtotal,
            ]);
        }

        $products->each(function (Product $product, int $index): void {
            $product->update([
                'view_count' => 100 + ($index * 73),
                'sold_count' => 10 + ($index * 3),
                'stock' => $index % 5 === 0 ? 5 : 40 + $index,
            ]);
        });

        $this->command?->info('Da tao 30 khach hang, 42 don hang va du lieu dashboard demo.');
    }
}
