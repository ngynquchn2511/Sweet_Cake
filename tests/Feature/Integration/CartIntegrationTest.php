<?php

namespace Tests\Feature\Integration;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\ShippingAddress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_add_product_to_cart(): void
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
        ]);

        $category = Category::factory()->create();

        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 120000,
            'discount_price' => 90000,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post(route('cart.add'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('carts', ['user_id' => $user->id]);
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => '2',
        ]);
    }

    public function test_authenticated_user_can_place_cod_order_from_cart(): void
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
        ]);

        $category = Category::factory()->create();

        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 120000,
            'discount_price' => 90000,
            'stock' => 20,
            'status' => 'active',
        ]);

        $cart = Cart::factory()->create(['user_id' => $user->id]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => $product->discount_price,
        ]);

        ShippingAddress::create([
            'user_id' => $user->id,
            'receiver_name' => 'Nguyen Van A',
            'phone' => '0909123456',
            'province' => 'Hồ Chí Minh',
            'district' => 'Quận 1',
            'ward' => 'Phường Bến Nghé',
            'address' => '123 Lê Lợi',
            'note' => 'Giao giờ hành chính',
            'is_default' => true,
        ]);

        $response = $this->actingAs($user)->post(route('checkout.cod'));

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'payment_method' => 'cod',
            'order_status' => 'pending',
        ]);
        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'quantity' => '2',
        ]);
        $this->assertDatabaseMissing('cart_items', ['cart_id' => $cart->id]);
    }
}
