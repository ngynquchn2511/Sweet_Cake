<?php

namespace Tests\Feature\Integration;

use App\Models\AdminLog;
use App\Models\Banner;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Message;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Review;
use App\Models\ShippingAddress;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Kiểm thử tích hợp (Controller ↔ Model ↔ DB qua HTTP) cho các test case còn Fail ở lần kiểm thử trước.
 * Tên test kèm mã test case (UT/IT) trong file Excel mà nó xác nhận.
 */
class RegressionFixesIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function customer(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    /** Khách có giỏ hàng chứa $qty sản phẩm + 1 địa chỉ mặc định. */
    private function customerWithCart(Product $product, int $qty = 1): User
    {
        $user = $this->customer();
        ShippingAddress::factory()->create(['user_id' => $user->id, 'is_default' => true]);
        $cart = Cart::create(['user_id' => $user->id]);
        $cart->cartItems()->create([
            'product_id' => $product->id,
            'quantity' => $qty,
            'price' => $product->getFinalPrice(),
        ]);

        return $user;
    }

    private function momoSignature(array $payload): string
    {
        $raw = 'accessKey=' . env('MOMO_ACCESS_KEY')
            . '&amount=' . ($payload['amount'] ?? '')
            . '&extraData=' . ($payload['extraData'] ?? '')
            . '&message=' . ($payload['message'] ?? '')
            . '&orderId=' . ($payload['orderId'] ?? '')
            . '&orderInfo=' . ($payload['orderInfo'] ?? '')
            . '&orderType=' . ($payload['orderType'] ?? '')
            . '&partnerCode=' . ($payload['partnerCode'] ?? '')
            . '&payType=' . ($payload['payType'] ?? '')
            . '&requestId=' . ($payload['requestId'] ?? '')
            . '&responseTime=' . ($payload['responseTime'] ?? '')
            . '&resultCode=' . ($payload['resultCode'] ?? '')
            . '&transId=' . ($payload['transId'] ?? '');

        return hash_hmac('sha256', $raw, (string) env('MOMO_SECRET_KEY'));
    }

    // ================= Module 1: Đăng nhập =================

    // IT-11, ST-88
    public function test_web_login_rejects_banned_account(): void
    {
        $user = $this->customer(['status' => 'banned']);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    // IT-09
    public function test_web_login_updates_last_login(): void
    {
        $user = $this->customer(['last_login' => null]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect();

        $this->assertNotNull($user->fresh()->last_login);
    }

    // ================= Module 2: Sản phẩm & Danh mục =================

    // SPDM-IT-019
    public function test_product_detail_api_only_includes_approved_reviews(): void
    {
        $product = Product::factory()->create(['status' => 'active']);
        Review::factory()->create(['product_id' => $product->id, 'status' => 'approved', 'rating' => 4]);
        Review::factory()->create(['product_id' => $product->id, 'status' => 'pending', 'rating' => 1]);
        Review::factory()->create(['product_id' => $product->id, 'status' => 'rejected', 'rating' => 1]);

        $this->getJson("/api/customer/products/{$product->slug}")
            ->assertOk()
            ->assertJsonCount(1, 'data.reviews')
            ->assertJsonPath('data.avg_rating', 4);
    }

    // SPDM-IT-035, SPDM-IT-036
    public function test_admin_api_can_toggle_and_duplicate_product(): void
    {
        Sanctum::actingAs($this->admin());
        $product = Product::factory()->create(['status' => 'active']);

        $this->putJson("/api/admin/products/{$product->id}/status", ['status' => 'inactive'])->assertOk();
        $this->assertSame('inactive', $product->fresh()->status);

        $this->postJson("/api/admin/products/{$product->id}/duplicate")
            ->assertCreated()
            ->assertJsonPath('data.slug', $product->slug . '-copy')
            ->assertJsonPath('data.status', 'inactive');
    }

    // SPDM-IT-049, SPDM-IT-050
    public function test_admin_api_can_update_category_status_and_order(): void
    {
        Sanctum::actingAs($this->admin());
        [$a, $b] = Category::factory()->count(2)->create();

        $this->putJson("/api/admin/categories/{$a->id}/status", ['status' => 'inactive'])->assertOk();
        $this->assertSame('inactive', $a->fresh()->status);

        // Trước khi sửa, route /{id} khai báo trước nên "update-order" bị hiểu là id → 404/500
        $this->putJson('/api/admin/categories/update-order', [
            'categories' => [
                ['id' => $a->id, 'display_order' => 5],
                ['id' => $b->id, 'display_order' => 1],
            ],
        ])->assertOk();

        $this->assertEquals(5, $a->fresh()->display_order);
        $this->assertEquals(1, $b->fresh()->display_order);
    }

    // SPDM-IT-068
    public function test_review_listing_routes_are_registered(): void
    {
        $product = Product::factory()->create();
        Review::factory()->create(['product_id' => $product->id, 'status' => 'approved']);

        $this->getJson("/api/customer/products/{$product->id}/reviews")->assertOk();

        Sanctum::actingAs($this->customer());
        $this->getJson('/api/customer/reviews/mine')->assertOk();
    }

    // ================= Module 3: Giỏ hàng & Đặt hàng =================

    // GHDH-UT-037, GHDH-ST-003
    public function test_web_add_to_cart_rejects_inactive_product(): void
    {
        $product = Product::factory()->create(['status' => 'inactive']);

        $this->actingAs($this->customer())
            ->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 1])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('cart_items', 0);
    }

    // GHDH-UT-038
    public function test_web_add_to_cart_rejects_quantity_over_stock(): void
    {
        $product = Product::factory()->create(['stock' => 2]);

        $this->actingAs($this->customer())
            ->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 3])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('cart_items', 0);
    }

    // GHDH-UT-039, GHDH-ST-038
    public function test_user_cannot_remove_item_from_another_users_cart(): void
    {
        $victimItem = CartItem::factory()->create();

        $this->actingAs($this->customer())->delete(route('cart.remove', $victimItem->id));

        $this->assertDatabaseHas('cart_items', ['id' => $victimItem->id]);
    }

    // GHDH-UT-040, KMQC-ST-025
    public function test_cod_checkout_applies_promotion_code(): void
    {
        $product = Product::factory()->create(['price' => 200000, 'discount_price' => null, 'stock' => 10]);
        $promotion = Promotion::factory()->create(['code' => 'GIAM10', 'discount_type' => 'percent', 'discount_value' => 10]);
        $user = $this->customerWithCart($product, 1);

        $this->actingAs($user)->post(route('checkout.cod'), ['promotion_code' => 'giam10'])->assertRedirect(route('dashboard'));

        $order = Order::where('user_id', $user->id)->firstOrFail();
        $this->assertEquals(20000, $order->discount_amount);
        $this->assertEquals(200000 + 30000 - 20000, $order->total_amount);
        $this->assertSame($promotion->id, $order->promotion_id);
        $this->assertSame(1, $promotion->fresh()->used_count);
    }

    // KMQC-UT-031
    public function test_customer_can_check_promotion_code_before_checkout(): void
    {
        $product = Product::factory()->create(['price' => 100000, 'discount_price' => null]);
        Promotion::factory()->create(['code' => 'FIX20K', 'discount_type' => 'fixed', 'discount_value' => 20000]);
        $user = $this->customerWithCart($product, 2);

        $this->actingAs($user)
            ->postJson(route('checkout.check-promotion'), ['code' => 'FIX20K'])
            ->assertOk()
            ->assertJson(['success' => true, 'discount_amount' => 20000]);
    }

    // GHDH-IT-018, GHDH-IT-019, GHDH-ST-027
    public function test_cart_count_and_sync_api(): void
    {
        $product = Product::factory()->create(['price' => 100000, 'discount_price' => null, 'stock' => 50]);
        $user = $this->customerWithCart($product, 3);
        $product->update(['price' => 150000]);

        Sanctum::actingAs($user);

        $this->getJson('/api/customer/cart/count')->assertOk()->assertJson(['count' => 3]);
        $this->postJson('/api/customer/cart/sync')->assertOk()->assertJsonPath('data.subtotal', 450000);
    }

    // GHDH-IT-039
    public function test_customer_can_cancel_order_via_api_and_stock_is_restored(): void
    {
        $user = $this->customer();
        $product = Product::factory()->create(['stock' => 5]);
        $order = Order::factory()->create(['user_id' => $user->id, 'order_status' => 'pending']);
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product->id, 'quantity' => 2]);

        Sanctum::actingAs($user);

        $this->putJson("/api/customer/orders/{$order->id}/cancel", ['reason' => 'Đặt nhầm'])
            ->assertOk()
            ->assertJsonPath('data.order_status', 'cancelled');

        $this->assertEquals(7, $product->fresh()->stock);
    }

    // GHDH-IT-063, GHDH-ST-036
    public function test_web_cancel_restores_stock_and_blocks_shipped_orders(): void
    {
        $user = $this->customer();
        $product = Product::factory()->create(['stock' => 1]);
        $pending = Order::factory()->create(['user_id' => $user->id, 'order_status' => 'pending']);
        OrderItem::factory()->create(['order_id' => $pending->id, 'product_id' => $product->id, 'quantity' => 4]);
        $shipping = Order::factory()->create(['user_id' => $user->id, 'order_status' => 'shipping']);

        $this->actingAs($user)->post(route('orders.cancel', $pending->id), ['reason' => 'Đổi ý'])->assertSessionHas('success');
        $this->actingAs($user)->post(route('orders.cancel', $shipping->id), ['reason' => 'Đổi ý'])->assertSessionHas('error');

        $this->assertEquals(5, $product->fresh()->stock);
        $this->assertSame('shipping', $shipping->fresh()->order_status);
    }

    // GHDH-IT-061
    public function test_checkout_rechecks_stock_at_order_time(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $user = $this->customerWithCart($product, 3);
        $product->update(['stock' => 0]); // admin hạ tồn kho sau khi khách đã thêm vào giỏ

        $this->actingAs($user)->post(route('checkout.cod'))->assertSessionHasErrors('cart');

        $this->assertDatabaseCount('orders', 0);
        $this->assertEquals(0, $product->fresh()->stock);
    }

    // GHDH-IT-061 (sản phẩm bị ngừng bán sau khi đã nằm trong giỏ)
    public function test_checkout_rejects_product_deactivated_after_adding_to_cart(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $user = $this->customerWithCart($product, 1);
        $product->update(['status' => 'inactive']);

        $this->actingAs($user)->post(route('orders.store'))->assertSessionHasErrors('cart');
        $this->assertDatabaseCount('orders', 0);
    }

    // GHDH-UT-035, GHDH-IT-051, GHDH-IT-055
    public function test_momo_payment_creates_real_order_and_sends_its_code(): void
    {
        Http::fake(['test-payment.momo.vn/*' => Http::response(['payUrl' => 'https://test-payment.momo.vn/pay/abc'])]);

        $product = Product::factory()->create(['price' => 100000, 'discount_price' => null, 'stock' => 10]);
        $user = $this->customerWithCart($product, 1);

        $this->actingAs($user)
            ->postJson(route('momo.payment'))
            ->assertOk()
            ->assertJsonPath('payUrl', 'https://test-payment.momo.vn/pay/abc');

        $order = Order::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('momo', $order->payment_method);
        $this->assertEquals(9, $product->fresh()->stock);

        Http::assertSent(function ($request) use ($order) {
            return $request['orderId'] === $order->order_code
                && $request['ipnUrl'] !== $request['redirectUrl']
                && str_ends_with($request['ipnUrl'], '/api/momo-ipn')
                && str_ends_with($request['redirectUrl'], '/momo-return');
        });
    }

    // GHDH-UT-036
    public function test_momo_ipn_requires_valid_signature(): void
    {
        $order = Order::factory()->create(['payment_method' => 'momo', 'payment_status' => 'unpaid']);
        $payload = ['orderId' => $order->order_code, 'resultCode' => 0, 'amount' => '230000'];

        $this->postJson('/api/momo-ipn', $payload + ['signature' => 'gia-mao'])->assertStatus(400);
        $this->assertSame('unpaid', $order->fresh()->payment_status);

        $this->postJson('/api/momo-ipn', $payload + ['signature' => $this->momoSignature($payload)])->assertNoContent();
        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    // ================= Module 4: Hồ sơ & Địa chỉ =================

    // HSDC-IT-004, HSDC-ST-021, HSDC-ST-028
    public function test_web_profile_form_updates_full_name_and_phone(): void
    {
        $user = $this->customer();

        $this->actingAs($user)
            ->patch('/profile', ['full_name' => 'Nguyễn Văn Mới', 'phone' => '0911222333', 'email' => $user->email])
            ->assertSessionHasNoErrors();

        $this->assertSame('Nguyễn Văn Mới', $user->fresh()->full_name);
        $this->assertSame('0911222333', $user->fresh()->phone);
    }

    // HSDC-UT-029, HSDC-ST-030
    public function test_web_cannot_delete_default_shipping_address(): void
    {
        $user = $this->customer();
        $address = ShippingAddress::factory()->create(['user_id' => $user->id, 'is_default' => true]);

        $this->actingAs($user)->delete(route('shipping-addresses.destroy', $address->id))->assertSessionHas('error');

        $this->assertDatabaseHas('shipping_addresses', ['id' => $address->id]);
    }

    // HSDC-UT-019
    public function test_web_and_api_reject_the_same_too_long_receiver_name(): void
    {
        $user = $this->customer();
        $payload = [
            'receiver_name' => str_repeat('a', 150),
            'phone' => '0901234567',
            'province' => 'Hà Nội',
            'district' => 'Cầu Giấy',
            'ward' => 'Dịch Vọng',
            'address' => '1 Trần Thái Tông',
        ];

        $this->actingAs($user)->post(route('shipping-addresses.store'), $payload)->assertSessionHasErrors('receiver_name');

        Sanctum::actingAs($user);
        $this->postJson('/api/customer/shipping-addresses', $payload)->assertJsonValidationErrors('receiver_name');
    }

    // HSDC-ST-016, HSDC-ST-017, HSDC-ST-029
    public function test_api_set_default_and_store_default_address(): void
    {
        $user = $this->customer();
        $old = ShippingAddress::factory()->create(['user_id' => $user->id, 'is_default' => true]);
        Sanctum::actingAs($user);

        $this->postJson('/api/customer/shipping-addresses', [
            'receiver_name' => 'Người nhận',
            'phone' => '0901234567',
            'province' => 'Hà Nội',
            'district' => 'Cầu Giấy',
            'ward' => 'Dịch Vọng',
            'address' => '1 Trần Thái Tông',
            'is_default' => true,
        ])->assertCreated();

        $this->assertFalse($old->fresh()->is_default);

        $this->putJson("/api/customer/shipping-addresses/{$old->id}/set-default")->assertOk();
        $this->assertTrue($old->fresh()->is_default);
        $this->assertSame(1, ShippingAddress::where('user_id', $user->id)->where('is_default', true)->count());
    }

    // HSDC-IT-042
    public function test_web_shipping_address_show_redirects_to_edit_form(): void
    {
        $user = $this->customer();
        $address = ShippingAddress::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('shipping-addresses.show', $address->id))
            ->assertRedirect(route('shipping-addresses.edit', $address->id));

        $this->actingAs($this->customer())->get(route('shipping-addresses.show', $address->id))->assertForbidden();
    }

    // HSDC-IT-009
    public function test_deleting_account_cleans_up_personal_data_without_orphans(): void
    {
        $product = Product::factory()->create();
        $user = $this->customerWithCart($product, 1);
        $usedAddress = ShippingAddress::where('user_id', $user->id)->first();
        $unusedAddress = ShippingAddress::factory()->create(['user_id' => $user->id]);
        $order = Order::factory()->create(['user_id' => $user->id, 'shipping_address_id' => $usedAddress->id]);
        Wishlist::create(['user_id' => $user->id, 'product_id' => $product->id]);
        Review::factory()->create(['user_id' => $user->id, 'product_id' => $product->id]);

        $this->actingAs($user)->delete('/profile', ['password' => 'password'])->assertRedirect('/');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('carts', ['user_id' => $user->id]);
        $this->assertDatabaseCount('cart_items', 0);
        $this->assertDatabaseMissing('wishlists', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('reviews', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('shipping_addresses', ['id' => $unusedAddress->id]);
        $this->assertNull($order->fresh()->user_id);
        $this->assertNull($usedAddress->fresh()->user_id);
    }

    // HSDC-IT-016
    public function test_both_profile_update_endpoints_share_one_implementation(): void
    {
        $user = $this->customer();
        Sanctum::actingAs($user);

        $this->putJson('/api/auth/update-profile', ['full_name' => 'Tên A', 'phone' => '0900000001'])->assertOk();
        $this->assertSame('Tên A', $user->fresh()->full_name);

        $this->putJson('/api/customer/profile', ['full_name' => 'Tên B', 'phone' => '0900000002'])->assertOk();
        $this->assertSame('Tên B', $user->fresh()->full_name);

        // Cùng 1 luật validate: thiếu phone bị từ chối ở cả 2 endpoint
        $this->putJson('/api/auth/update-profile', ['full_name' => 'X'])->assertJsonValidationErrors('phone');
        $this->putJson('/api/customer/profile', ['full_name' => 'X'])->assertJsonValidationErrors('phone');
    }

    // BUG-029 (web): đổi mật khẩu thu hồi token ở thiết bị khác
    public function test_web_password_change_revokes_api_tokens(): void
    {
        $user = $this->customer();
        $user->createToken('mobile');

        $this->actingAs($user)->put(route('password.update'), [
            'current_password' => 'password',
            'password' => 'MatKhauMoi@123',
            'password_confirmation' => 'MatKhauMoi@123',
        ])->assertSessionHasNoErrors();

        $this->assertSame(0, $user->tokens()->count());
    }

    // ================= Module 5: Liên hệ, Tin nhắn & Trang chủ =================

    // LHTN-UT-010, LHTN-UT-011, LHTN-ST-012
    public function test_logged_in_user_can_send_message_with_subject_and_content_only(): void
    {
        $user = $this->customer();

        $this->actingAs($user)
            ->postJson('/api/messages', ['subject' => 'Hỏi giờ mở cửa', 'content' => 'Shop mở cửa mấy giờ?'])
            ->assertCreated();

        $this->assertDatabaseHas('messages', ['user_id' => $user->id, 'subject' => 'Hỏi giờ mở cửa', 'guest_name' => null]);
    }

    // LHTN-UT-010 (nhánh còn lại): khách vãng lai vẫn bắt buộc họ tên + email
    public function test_guest_message_still_requires_name_and_email(): void
    {
        $this->postJson('/api/messages', ['subject' => 'Hỏi', 'content' => 'Nội dung'])
            ->assertJsonValidationErrors(['guest_name', 'guest_email']);
    }

    // LHTN-IT-026
    public function test_contact_and_message_endpoints_are_rate_limited(): void
    {
        $payload = ['name' => 'A', 'email' => 'a@a.vn', 'phone' => '0900000000', 'subject' => 'S', 'message' => 'M'];

        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/customer/contact', $payload)->assertCreated();
        }
        $this->postJson('/api/customer/contact', $payload)->assertStatus(429);

        $guest = ['guest_name' => 'B', 'guest_email' => 'b@b.vn', 'subject' => 'S', 'content' => 'C'];
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/messages', $guest)->assertCreated();
        }
        $this->postJson('/api/messages', $guest)->assertStatus(429);
    }

    // HOME-UT-001, HOME-IT-002, HOME-ST-003
    public function test_home_page_lists_active_products_and_banners(): void
    {
        Product::factory()->count(3)->create(['status' => 'active']);
        Product::factory()->create(['status' => 'inactive']);
        Banner::factory()->create(['position' => 'homepage_main', 'status' => 'active']);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Home')
                ->has('products', 3)
                ->has('banners.homepage_main', 1));
    }

    // ================= Module 6: Quản trị người dùng / đơn hàng / SP / DM =================

    // QTA-UT-016, QTA-IT-036, QTA-UT-020
    public function test_admin_can_confirm_order_and_completing_does_not_mark_paid(): void
    {
        $admin = $this->admin();
        $order = Order::factory()->create(['payment_method' => 'cod', 'payment_status' => 'unpaid']);

        $this->actingAs($admin)->put(route('admin.orders.update-status', $order->id), ['order_status' => 'confirmed'])
            ->assertSessionHasNoErrors();
        $this->assertSame('confirmed', $order->fresh()->order_status);

        $this->actingAs($admin)->put(route('admin.orders.update-status', $order->id), ['order_status' => 'completed']);
        $this->assertSame('unpaid', $order->fresh()->payment_status);
    }

    // QTA-UT-017, QTA-IT-038
    public function test_admin_payment_status_only_accepts_database_values(): void
    {
        $order = Order::factory()->create();

        $this->actingAs($this->admin())
            ->put(route('admin.orders.update-payment', $order->id), ['payment_status' => 'pending'])
            ->assertSessionHasErrors('payment_status');

        $this->assertSame('unpaid', $order->fresh()->payment_status);
    }

    // QTA-IT-043, QTA-UT-040, QTA-ST-037
    public function test_web_and_api_accept_the_same_order_statuses(): void
    {
        $admin = $this->admin();
        $order = Order::factory()->create();

        $this->actingAs($admin)->put(route('admin.orders.update-status', $order->id), ['order_status' => 'refunded'])
            ->assertSessionHasNoErrors();

        Sanctum::actingAs($admin);
        $this->putJson("/api/admin/orders/{$order->id}/status", ['status' => 'refunded'])->assertOk();
        $this->putJson("/api/admin/orders/{$order->id}/status", ['status' => 'khong-hop-le'])->assertJsonValidationErrors('status');
    }

    // QTA-UT-022, QTA-UT-024, QTA-ST-036
    public function test_admin_update_with_empty_slug_generates_unique_slug(): void
    {
        $admin = $this->admin();
        $category = Category::factory()->create();
        Product::factory()->create(['slug' => 'banh-kem-dau']);
        $product = Product::factory()->create(['category_id' => $category->id]);

        $this->actingAs($admin)->put(route('admin.products.update', $product->id), [
            'category_id' => $category->id,
            'name' => 'Bánh kem dâu',
            'slug' => '',
            'price' => 100000,
            'stock' => 5,
            'unit' => 'chiếc',
            'status' => 'active',
        ])->assertSessionHasNoErrors();

        $this->assertSame('banh-kem-dau-1', $product->fresh()->slug);

        // Danh mục khác đã giữ slug "banh-mi-ngot" (dù khác tên) → danh mục đang sửa phải nhận slug có hậu tố
        Category::factory()->create(['name' => 'Danh mục cũ', 'slug' => 'banh-mi-ngot']);
        $this->actingAs($admin)->put(route('admin.categories.update', $category->id), [
            'name' => 'Bánh mì ngọt',
            'slug' => '',
            'status' => 'active',
        ])->assertSessionHasNoErrors();

        $this->assertSame('banh-mi-ngot-1', $category->fresh()->slug);
    }

    // QTA-UT-023, QTA-UT-039
    public function test_category_name_must_be_unique_via_web_and_api(): void
    {
        $admin = $this->admin();
        Category::factory()->create(['name' => 'Bánh kem', 'slug' => 'banh-kem']);

        $this->actingAs($admin)->post(route('admin.categories.store'), ['name' => 'Bánh kem', 'status' => 'active'])
            ->assertSessionHasErrors('name');

        Sanctum::actingAs($admin);
        $this->postJson('/api/admin/categories', ['name' => 'Bánh kem', 'slug' => 'banh-kem-2', 'status' => 'active'])
            ->assertJsonValidationErrors('name');
    }

    // QTA-UT-034, QTA-UT-035, QTA-UT-036, QTA-UT-037, QTMR-UT-023, KMQC-IT-004, KMQC-IT-033
    public function test_admin_lists_ignore_unknown_sort_columns(): void
    {
        $admin = $this->admin();

        foreach (['users', 'orders', 'products', 'categories', 'reviews', 'promotions'] as $resource) {
            $this->actingAs($admin)->get("/admin/{$resource}?sort_by=cot_khong_ton_tai&sort_order=hack")->assertOk();
        }

        Sanctum::actingAs($admin);
        $this->getJson('/api/admin/banners?sort_by=cot_khong_ton_tai')->assertOk();
    }

    // QTA-ST-006, QTA-ST-007, QTA-ST-008, QTA-ST-009, QTA-ST-034
    public function test_admin_customer_tools_work_from_web_session(): void
    {
        Mail::fake();
        $admin = $this->admin();
        $customer = $this->customer();

        $this->actingAs($admin)->get(route('admin.users.export'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertSee($customer->email);

        $this->actingAs($admin)->getJson(route('admin.users.statistics'))->assertOk()->assertJsonPath('total_customers', 1);

        $response = $this->actingAs($admin)->postJson(route('admin.users.reset-password', $customer->id))->assertOk();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check($response->json('new_password'), $customer->fresh()->password));

        $this->actingAs($admin)->getJson(route('admin.users.activity-log', $customer->id))
            ->assertOk()
            ->assertJsonPath('data.0.action', 'reset_password');
    }

    // Lỗi phát hiện thêm: route name API đè route name web → sau khi lưu bị chuyển sang /api/admin/...
    public function test_admin_redirects_after_save_stay_in_admin_web_area(): void
    {
        $this->assertSame(url('/admin/products'), route('admin.products.index'));
        $this->assertSame(url('/admin/users'), route('admin.users.index'));
        $this->assertSame(url('/admin/promotions'), route('admin.promotions.index'));
        $this->assertSame(url('/admin/orders'), route('admin.orders.index'));
        $this->assertSame(url('/api/admin/products'), route('api.admin.products.index'));
    }

    // ================= Module 7: Khuyến mãi & Banner =================

    // KMQC-UT-040
    public function test_banner_with_missing_image_can_be_deleted(): void
    {
        Sanctum::actingAs($this->admin());
        $banner = Banner::factory()->create(['image' => '']);

        $this->deleteJson("/api/admin/banners/{$banner->id}")->assertOk();
        $this->assertDatabaseMissing('banners', ['id' => $banner->id]);
    }

    // KMQC-IT-052, KMQC-IT-053, KMQC-IT-054, KMQC-ST-012, KMQC-ST-023
    public function test_active_banners_are_served_for_every_position(): void
    {
        foreach (['homepage_main', 'homepage_sub', 'sidebar', 'footer'] as $position) {
            Banner::factory()->create(['position' => $position, 'status' => 'active']);
        }
        Banner::factory()->create(['position' => 'sidebar', 'status' => 'inactive']);

        foreach (['homepage_main', 'homepage_sub', 'sidebar', 'footer'] as $position) {
            $this->getJson("/api/customer/banners?position={$position}")->assertOk()->assertJsonCount(1, 'data');
        }

        Sanctum::actingAs($this->admin());
        $this->getJson('/api/admin/banners/active')->assertOk()->assertJsonCount(4, 'data');
    }

    // KMQC-ST-007, KMQC-ST-008, KMQC-ST-009, KMQC-ST-024
    public function test_admin_promotion_tools_work_from_web_session(): void
    {
        $admin = $this->admin();
        $promotion = Promotion::factory()->create(['code' => 'TET2026']);

        $code = $this->actingAs($admin)->getJson(route('admin.promotions.generate-code'))->assertOk()->json('code');
        $this->assertStringStartsWith('SALE', $code);

        $this->actingAs($admin)->postJson(route('admin.promotions.duplicate', $promotion->id))
            ->assertCreated()
            ->assertJsonPath('data.code', 'TET2026-COPY');

        $this->actingAs($admin)->getJson(route('admin.promotions.usage-report', $promotion->id))->assertOk()->assertJsonPath('data.total_orders', 0);
        $this->actingAs($admin)->getJson(route('admin.promotions.statistics'))->assertOk();
    }

    // ================= Module 8: Dashboard / Report / Log =================

    // QTB-UT-006, QTB-UT-012
    public function test_dashboard_separates_out_of_stock_from_low_stock_and_uses_all_helpers(): void
    {
        Product::factory()->create(['stock' => 0]);
        Product::factory()->count(2)->create(['stock' => 5]);
        Product::factory()->create(['stock' => 50]);

        $this->actingAs($this->admin())->get('/admin/dashboard')
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Dashboard')
                ->where('stats.out_of_stock', 1)
                ->where('stats.low_stock', 2)
                ->has('stats.revenue_chart_7_days.labels', 7)
                ->has('stats.revenue_by_month.labels', 12)
                ->has('stats.top_customers'));
    }

    // QTB-IT-035, QTB-ST-023
    public function test_admin_actions_are_written_to_admin_log(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();
        $order = Order::factory()->create();

        $this->actingAs($admin)->put(route('admin.users.update-status', $customer->id), ['status' => 'banned']);
        $this->actingAs($admin)->put(route('admin.orders.update-status', $order->id), ['order_status' => 'processing']);

        Sanctum::actingAs($admin);
        $logs = $this->getJson('/api/admin/logs')->assertOk()->json('data.data');

        $this->assertCount(2, $logs);
        $this->assertEqualsCanonicalizing(['users', 'orders'], array_column($logs, 'table_name'));
    }

    // QTB-IT-037, QTB-IT-038, QTB-IT-039, QTB-IT-040, QTB-IT-041, QTB-UT-040, QTB-ST-024
    public function test_all_admin_log_routes_work(): void
    {
        $admin = $this->admin();
        $log = AdminLog::create(['user_id' => $admin->id, 'action' => 'update', 'table_name' => 'products', 'record_id' => 7]);
        $old = AdminLog::create(['user_id' => $admin->id, 'action' => 'delete', 'table_name' => 'products', 'record_id' => 8]);
        $old->forceFill(['created_at' => now()->subDays(200)])->save();

        Sanctum::actingAs($admin);
        $this->getJson('/api/admin/logs/by-record?table_name=products&record_id=7')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/admin/logs/statistics')->assertOk()->assertJsonPath('data.total_logs', 2);
        $this->getJson('/api/admin/logs/recent')->assertOk()->assertJsonCount(2, 'data');

        $export = $this->get('/api/admin/logs/export')->assertOk();
        $this->assertStringContainsString('text/csv', $export->headers->get('Content-Type'));
        $this->assertStringContainsString('products', $export->getContent());

        $this->postJson('/api/admin/logs/clear-old', ['days' => 90])->assertOk();
        $this->assertDatabaseMissing('admin_logs', ['id' => $old->id]);
        $this->assertDatabaseHas('admin_logs', ['id' => $log->id]);
    }

    // QTB-ST-007, QTB-ST-008, QTB-ST-022
    public function test_admin_report_subpages_and_export_work_from_web_session(): void
    {
        $admin = $this->admin();
        Product::factory()->create(['stock' => 0]);

        $this->actingAs($admin)->getJson(route('admin.reports.inventory'))->assertOk()->assertJsonPath('data.out_of_stock_count', 1);
        foreach (['revenue', 'products', 'categories', 'customers', 'order-status', 'payment-methods'] as $report) {
            $this->actingAs($admin)->getJson(route("admin.reports.{$report}"))->assertOk();
        }

        $this->actingAs($admin)->get(route('admin.reports.export'))->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    // ================= Module 9: Đánh giá & Tin nhắn =================

    // QTMR-UT-008, QTMR-UT-009, QTMR-UT-010, QTMR-UT-022, QTMR-ST-003, QTMR-ST-004
    public function test_quick_approve_and_reject_record_reviewer(): void
    {
        $admin = $this->admin();
        $review = Review::factory()->create(['status' => 'pending']);

        $this->actingAs($admin)->put(route('admin.reviews.approve', $review->id))->assertSessionHas('success');
        $this->assertSame('approved', $review->fresh()->status);
        $this->assertSame($admin->id, $review->fresh()->reviewed_by);
        $this->assertNotNull($review->fresh()->reviewed_at);

        $this->actingAs($admin)->put(route('admin.reviews.reject', $review->id));
        $this->assertSame('rejected', $review->fresh()->status);

        Sanctum::actingAs($admin);
        $this->putJson("/api/admin/reviews/{$review->id}/approve")->assertOk()->assertJsonPath('data.status', 'approved');
        $this->assertDatabaseHas('admin_logs', ['table_name' => 'reviews', 'record_id' => $review->id]);
    }

    // QTMR-UT-007, QTMR-ST-005
    public function test_admin_can_view_review_detail(): void
    {
        $review = Review::factory()->create();

        $this->actingAs($this->admin())->getJson(route('admin.reviews.show', $review->id))
            ->assertOk()
            ->assertJsonPath('data.id', $review->id)
            ->assertJsonPath('data.product.id', $review->product_id);
    }

    // QTMR-UT-006
    public function test_admin_deleting_review_removes_attached_images(): void
    {
        $dir = public_path('uploads/reviews');
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $path = 'uploads/reviews/test-' . uniqid() . '.jpg';
        file_put_contents(public_path($path), 'x');
        $review = Review::factory()->create(['images' => [$path]]);

        $this->actingAs($this->admin())->delete(route('admin.reviews.destroy', $review->id));

        $this->assertFileDoesNotExist(public_path($path));
        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
    }

    // QTMR-UT-018
    public function test_admin_reply_emails_the_customer(): void
    {
        Mail::fake();
        $message = Message::create(['guest_name' => 'Lan', 'guest_email' => 'lan@example.com', 'subject' => 'Hỏi', 'content' => 'Nội dung']);

        $this->actingAs($this->admin())->post(route('admin.messages.reply', $message->id), ['admin_reply' => 'Chào bạn'])
            ->assertSessionHas('success');

        Mail::assertSent(\App\Mail\MessageRepliedMail::class, fn ($mail) => $mail->hasTo('lan@example.com'));
        $this->assertSame('replied', $message->fresh()->status);
    }

    // QTMR-UT-019, LHTN-ST-005, QTMR-ST-020
    public function test_customer_can_read_admin_replies(): void
    {
        $user = $this->customer();
        Message::create(['user_id' => $user->id, 'subject' => 'Hỏi', 'content' => 'Q', 'admin_reply' => 'Trả lời', 'status' => 'replied']);

        Sanctum::actingAs($user);
        $this->getJson('/api/customer/messages')->assertOk()->assertJsonPath('data.data.0.admin_reply', 'Trả lời');
    }

    // QTMR-UT-020, QTMR-UT-021, QTMR-ST-021
    public function test_admin_can_reply_to_contact_message(): void
    {
        Mail::fake();
        $contact = ContactMessage::factory()->create(['status' => 'new', 'admin_reply' => null, 'replied_at' => null]);

        $this->actingAs($this->admin())->post(route('admin.contacts.reply', $contact->id), ['admin_reply' => 'Cảm ơn bạn'])
            ->assertSessionHas('success');

        $contact->refresh();
        $this->assertSame('Cảm ơn bạn', $contact->admin_reply);
        $this->assertSame('replied', $contact->status);
        $this->assertNotNull($contact->replied_at);
        Mail::assertSent(\App\Mail\ContactRepliedMail::class);
    }
}
