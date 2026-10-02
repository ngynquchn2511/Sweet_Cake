<?php

namespace Tests\Feature\System;

use App\Models\AdminLog;
use App\Models\Cart;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Message;
use App\Models\Order;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Review;
use App\Models\ShippingAddress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kiểm thử hệ thống: đi trọn hành trình người dùng qua các route web thật và kiểm tra
 * trang Inertia được render (file React phải tồn tại - config inertia.testing.ensure_pages_exist)
 * cùng dữ liệu hiển thị. Tên test kèm mã test case ST trong file Excel.
 */
class RegressionFixesSystemTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    // SPDM-ST-006, SPDM-ST-015, SPDM-ST-024, SPDM-ST-040
    public function test_customer_views_product_detail_then_adds_it_to_cart(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 10]);
        Review::factory()->create(['product_id' => $product->id, 'status' => 'approved', 'rating' => 5]);
        Review::factory()->create(['product_id' => $product->id, 'status' => 'approved', 'rating' => 3]);
        Review::factory()->create(['product_id' => $product->id, 'status' => 'pending', 'rating' => 1]);

        $this->actingAs($user)->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Customer/Products/Show')
                ->where('product.id', $product->id)
                ->where('product.avg_rating', 4)
                ->where('product.review_count', 2)
                ->has('product.reviews', 2)
                ->where('isInWishlist', false));

        $this->assertEquals(1, $product->fresh()->view_count - $product->view_count);

        $this->actingAs($user)->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 2])
            ->assertSessionHas('success');

        $this->actingAs($user)->get(route('cart.index'))
            ->assertInertia(fn ($page) => $page->component('Cart/Index')->has('cartItems', 1)->where('totals.total_items', 2));
    }

    // SPDM-ST-009, SPDM-ST-030, SPDM-ST-031, SPDM-ST-032, SPDM-ST-033, SPDM-ST-041
    public function test_customer_wishlist_journey(): void
    {
        $user = User::factory()->create();
        [$a, $b] = Product::factory()->count(2)->create();

        $this->actingAs($user)->postJson(route('wishlist.toggle'), ['product_id' => $a->id])->assertJson(['is_in_wishlist' => true]);
        $this->actingAs($user)->postJson(route('wishlist.toggle'), ['product_id' => $b->id])->assertJson(['is_in_wishlist' => true]);

        // Tải lại trang: trạng thái vẫn được giữ, badge Navbar đếm đúng
        $this->actingAs($user)->get(route('products.show', $a->slug))
            ->assertInertia(fn ($page) => $page->where('isInWishlist', true)->where('wishlistCount', 2));

        $this->actingAs($user)->get(route('wishlist.index'))
            ->assertInertia(fn ($page) => $page->component('Wishlist/Index')->has('items', 2));

        $this->actingAs($user)->deleteJson(route('wishlist.remove', $a->id))->assertOk();
        $this->actingAs($user)->getJson(route('wishlist.count'))->assertJson(['count' => 1]);

        $this->actingAs($user)->deleteJson(route('wishlist.clear'))->assertOk();
        $this->actingAs($user)->get('/')->assertInertia(fn ($page) => $page->where('wishlistCount', 0));
    }

    // GHDH-ST-008, GHDH-ST-035
    public function test_customer_clears_whole_cart_from_cart_page(): void
    {
        $user = User::factory()->create();
        $cart = Cart::create(['user_id' => $user->id]);
        foreach (Product::factory()->count(3)->create() as $product) {
            $cart->cartItems()->create(['product_id' => $product->id, 'quantity' => 1, 'price' => $product->getFinalPrice()]);
        }

        $this->actingAs($user)->from(route('cart.index'))->delete(route('cart.clear'))->assertRedirect(route('cart.index'));

        $this->actingAs($user)->get(route('cart.index'))->assertInertia(fn ($page) => $page->has('cartItems', 0));
    }

    // GHDH-ST-027
    public function test_cart_page_reflects_new_price_after_admin_changes_it(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 100000, 'discount_price' => null]);
        Cart::create(['user_id' => $user->id])->cartItems()->create(['product_id' => $product->id, 'quantity' => 1, 'price' => 100000]);

        $product->update(['price' => 150000]);

        $this->actingAs($user)->get(route('cart.index'))
            ->assertInertia(fn ($page) => $page
                ->where('cartItems.0.price', '150000.00')
                ->where('totals.subtotal', 150000)
                ->where('syncNotice', 'Giỏ hàng đã được cập nhật theo giá và tồn kho mới nhất.'));
    }

    // GHDH-ST-020, GHDH-ST-037, ST-21
    public function test_customer_returning_from_momo_sees_paid_result(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id, 'payment_method' => 'momo']);
        $payload = ['orderId' => $order->order_code, 'resultCode' => '0', 'message' => 'Successful.', 'amount' => '230000'];
        $raw = 'accessKey=' . env('MOMO_ACCESS_KEY') . '&amount=230000&extraData=&message=Successful.&orderId=' . $order->order_code
            . '&orderInfo=&orderType=&partnerCode=&payType=&requestId=&responseTime=&resultCode=0&transId=';
        $payload['signature'] = hash_hmac('sha256', $raw, (string) env('MOMO_SECRET_KEY'));

        $this->actingAs($user)->get(route('momo.return', $payload))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Checkout/PaymentResult')
                ->where('success', true)
                ->where('order.payment_status', 'paid'));
    }

    // GHDH-ST-026, GHDH-ST-034, GHDH-ST-036, GHDH-ST-038, KMQC-ST-010, KMQC-ST-025
    public function test_full_purchase_journey_with_promotion_and_cancellation(): void
    {
        $user = User::factory()->create();
        ShippingAddress::factory()->create(['user_id' => $user->id, 'is_default' => true]);
        $product = Product::factory()->create(['price' => 300000, 'discount_price' => null, 'stock' => 5]);
        Promotion::factory()->create(['code' => 'SWEET50K', 'discount_type' => 'fixed', 'discount_value' => 50000]);

        $this->get(route('products.index'))->assertInertia(fn ($page) => $page->component('Customer/Products/Index')->has('categories'));
        $this->actingAs($user)->get(route('products.show', $product->slug))->assertOk();
        $this->actingAs($user)->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 2]);
        $this->actingAs($user)->put(route('cart.update', $user->cart->cartItems->first()->id), ['quantity' => 1]);

        $this->actingAs($user)->postJson(route('checkout.check-promotion'), ['code' => 'SWEET50K'])
            ->assertJson(['success' => true, 'discount_amount' => 50000]);

        $this->actingAs($user)->post(route('checkout.cod'), ['promotion_code' => 'SWEET50K'])->assertRedirect(route('dashboard'));

        $order = Order::where('user_id', $user->id)->firstOrFail();
        $this->assertEquals(300000 + 30000 - 50000, $order->total_amount);
        $this->assertEquals(4, $product->fresh()->stock);

        $this->actingAs($user)->get(route('orders.my-orders'))->assertInertia(fn ($page) => $page->component('MyOrders')->has('orders.data', 1));
        $this->actingAs($user)->get(route('orders.show', $order->id))->assertInertia(fn ($page) => $page->component('Order/OrderDetail'));

        $this->actingAs($user)->post(route('orders.cancel', $order->id), ['reason' => 'Đổi ý'])->assertSessionHas('success');
        $this->assertSame('cancelled', $order->fresh()->order_status);
        $this->assertEquals(5, $product->fresh()->stock);

        // Trang /checkout (trước đây render trang không tồn tại) nay dẫn về giỏ hàng có sẵn bước thanh toán
        $this->actingAs($user)->get(route('checkout.index'))->assertRedirect(route('cart.index'));
    }

    // LHTN-ST-005, LHTN-ST-012, QTMR-ST-020
    public function test_customer_sends_question_and_reads_reply_on_my_messages_page(): void
    {
        $user = User::factory()->create();

        $this->get(route('contact'))->assertOk()->assertInertia(fn ($page) => $page->component('Contact'));

        $this->actingAs($user)->postJson('/api/messages', ['subject' => 'Bánh không đường?', 'content' => 'Shop có bánh cho người ăn kiêng không?'])
            ->assertCreated();
        $this->postJson('/api/customer/contact', [
            'name' => $user->full_name, 'email' => $user->email, 'phone' => '0900000000',
            'subject' => 'Đặt tiệc', 'message' => 'Tôi muốn đặt 50 bánh',
        ])->assertCreated();

        $message = Message::firstOrFail();
        $contact = ContactMessage::firstOrFail();
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.messages.reply', $message->id), ['admin_reply' => 'Có ạ']);
        $this->actingAs($admin)->post(route('admin.contacts.reply', $contact->id), ['admin_reply' => 'Shop sẽ gọi lại']);

        $this->actingAs($user)->get(route('messages.mine'))
            ->assertInertia(fn ($page) => $page
                ->component('Messages/Index')
                ->where('messages.data.0.admin_reply', 'Có ạ')
                ->where('contacts.0.admin_reply', 'Shop sẽ gọi lại'));
    }

    // QTA-ST-020, QTA-ST-023, QTA-ST-035, SPDM-ST-018, SPDM-ST-019, SPDM-ST-021
    public function test_admin_product_and_category_screens(): void
    {
        $admin = $this->admin();
        $product = Product::factory()->create(['status' => 'active']);
        [$c1, $c2] = Category::factory()->count(2)->create();

        $this->actingAs($admin)->get("/admin/products/{$product->id}")
            ->assertOk()->assertInertia(fn ($page) => $page->component('Admin/Products/Show')->where('product.id', $product->id));
        $this->actingAs($admin)->get("/admin/categories/{$c1->id}")
            ->assertOk()->assertInertia(fn ($page) => $page->component('Admin/Categories/Show')->where('category.id', $c1->id));

        $this->actingAs($admin)->putJson(route('admin.products.update-status', $product->id), ['status' => 'inactive'])->assertOk();
        $this->assertSame('inactive', $product->fresh()->status);

        $copyId = $this->actingAs($admin)->postJson(route('admin.products.duplicate', $product->id))->assertCreated()->json('data.id');
        $this->actingAs($admin)->get("/admin/products/{$copyId}/edit")->assertOk();

        $this->actingAs($admin)->putJson(route('admin.categories.update-order'), [
            'categories' => [['id' => $c1->id, 'display_order' => 1], ['id' => $c2->id, 'display_order' => 0]],
        ])->assertOk();
        $this->actingAs($admin)->get('/admin/categories?sort_by=display_order&sort_order=asc')
            ->assertInertia(fn ($page) => $page->where('categories.data.0.id', $c2->id));
    }

    // KMQC-ST-006, KMQC-ST-009, KMQC-ST-024
    public function test_admin_promotion_detail_screen_shows_usage(): void
    {
        $admin = $this->admin();
        $promotion = Promotion::factory()->create();
        Order::factory()->create(['promotion_id' => $promotion->id, 'discount_amount' => 20000]);

        $this->actingAs($admin)->get("/admin/promotions/{$promotion->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Promotions/Show')
                ->where('usage.total_orders', 1)
                ->where('usage.total_discount_given', 20000));
    }

    // QTB-ST-014, QTB-ST-015, QTB-ST-016, QTB-ST-023, QTB-ST-024, QTB-ST-025
    public function test_admin_activity_log_screen(): void
    {
        $admin = $this->admin();
        $customer = User::factory()->create();

        $this->actingAs($admin)->put(route('admin.users.update-status', $customer->id), ['status' => 'banned']);
        $old = AdminLog::create(['user_id' => $admin->id, 'action' => 'delete', 'table_name' => 'products', 'record_id' => 1]);
        $old->forceFill(['created_at' => now()->subDays(120)])->save();

        $this->actingAs($admin)->get(route('admin.logs.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Logs/Index')
                ->has('logs.data', 2)
                ->where('stats.total_logs', 2));

        $export = $this->actingAs($admin)->get(route('admin.logs.export'))->assertOk();
        $this->assertStringContainsString('update_status', $export->getContent());

        $this->actingAs($admin)->post(route('admin.logs.clear-old'), ['days' => 90])->assertSessionHas('success');
        $this->assertDatabaseMissing('admin_logs', ['id' => $old->id]);
    }

    // QTMR-ST-003, QTMR-ST-004, QTMR-ST-005, QTMR-ST-019, QTMR-ST-021
    public function test_admin_moderation_and_contact_screens(): void
    {
        $admin = $this->admin();
        [$r1, $r2] = Review::factory()->count(2)->create(['status' => 'pending']);
        $contact = ContactMessage::factory()->create(['status' => 'new']);

        $this->actingAs($admin)->get('/admin/reviews')->assertInertia(fn ($page) => $page->component('Admin/Reviews/Index')->where('stats.pending', 2));
        $this->actingAs($admin)->getJson(route('admin.reviews.show', $r1->id))->assertOk();
        $this->actingAs($admin)->put(route('admin.reviews.approve', $r1->id));
        $this->actingAs($admin)->put(route('admin.reviews.reject', $r2->id));
        $this->actingAs($admin)->get('/admin/reviews')->assertInertia(fn ($page) => $page->where('stats.approved', 1)->where('stats.rejected', 1));

        $this->actingAs($admin)->get(route('admin.contacts.index'))
            ->assertOk()->assertInertia(fn ($page) => $page->component('Admin/Contacts/Index')->has('contacts.data', 1));
        $this->actingAs($admin)->get(route('admin.contacts.show', $contact->id))
            ->assertOk()->assertInertia(fn ($page) => $page->component('Admin/Contacts/Detail'));
        $this->assertSame('read', $contact->fresh()->status);
    }

    // ST-11 (banner đúng vị trí trên trang chủ) - bổ sung sau khi nối dữ liệu banner vào trang chủ
    public function test_home_page_places_banners_by_position(): void
    {
        foreach (['homepage_main', 'homepage_sub', 'sidebar', 'footer'] as $i => $position) {
            \App\Models\Banner::factory()->create(['position' => $position, 'status' => 'active', 'title' => "Banner {$i}"]);
        }

        $this->get('/')->assertInertia(fn ($page) => $page
            ->where('banners.homepage_main.0.title', 'Banner 0')
            ->where('banners.homepage_sub.0.title', 'Banner 1')
            ->where('banners.sidebar.0.title', 'Banner 2')
            ->where('banners.footer.0.title', 'Banner 3'));
    }
}
