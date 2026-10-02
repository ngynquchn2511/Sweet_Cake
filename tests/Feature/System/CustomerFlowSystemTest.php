<?php

namespace Tests\Feature\System;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\ShippingAddress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerFlowSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_page_is_accessible(): void
    {
        $this->get('/products')
            ->assertOk();
    }

    public function test_guest_user_is_redirected_when_trying_to_access_cart(): void
    {
        $this->get(route('cart.index'))
            ->assertRedirect('/login');
    }

    public function test_authenticated_customer_can_complete_cod_checkout(): void
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
        ]);

        $category = Category::factory()->create();

        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 150000,
            'discount_price' => 120000,
            'stock' => 20,
            'status' => 'active',
        ]);

        $cart = Cart::factory()->create(['user_id' => $user->id]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => $product->discount_price,
        ]);

        ShippingAddress::create([
            'user_id' => $user->id,
            'receiver_name' => 'Trần Văn B',
            'phone' => '0912345678',
            'province' => 'Đà Nẵng',
            'district' => 'Hải Châu',
            'ward' => 'Phường Thanh Bình',
            'address' => '45 Nguyễn Chí Thanh',
            'note' => 'Giao trong ngày',
            'is_default' => true,
        ]);

        $this->actingAs($user)
            ->post(route('checkout.cod'))
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'payment_method' => 'cod',
            'order_status' => 'pending',
        ]);
    }
}
