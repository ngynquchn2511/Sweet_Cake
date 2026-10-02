<?php

namespace Tests\Unit;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartCalculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_subtotal_uses_discount_price_when_present(): void
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

        $cart = Cart::factory()->create([
            'user_id' => $user->id,
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => $product->discount_price,
        ]);

        $this->assertSame(180000.0, (float) $cart->fresh()->getSubtotal());
    }

    public function test_cart_total_items_sums_quantity_across_products(): void
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
        ]);

        $category = Category::factory()->create();

        $productA = Product::factory()->create([
            'category_id' => $category->id,
            'status' => 'active',
        ]);

        $productB = Product::factory()->create([
            'category_id' => $category->id,
            'status' => 'active',
        ]);

        $cart = Cart::factory()->create([
            'user_id' => $user->id,
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $productA->id,
            'quantity' => 2,
            'price' => $productA->price,
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $productB->id,
            'quantity' => 3,
            'price' => $productB->price,
        ]);

        $this->assertSame(5, (int) $cart->fresh()->getTotalItems());
    }
}
