<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\PromotionController as AdminPromotionController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\ContactController as AdminContactController;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Banner;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingAddress;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Kiểm thử đơn vị (Unit) cho các test case UT còn Fail ở lần kiểm thử trước.
 * Mỗi test ghi rõ mã test case tương ứng trong file Excel.
 */
class RegressionFixesUnitTest extends TestCase
{
    use RefreshDatabase;

    // SPDM-UT-072
    public function test_wishlist_model_toggle_and_is_in_wishlist(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->assertTrue(Wishlist::toggle($user->id, $product->id));
        $this->assertTrue(Wishlist::isInWishlist($user->id, $product->id));
        $this->assertFalse(Wishlist::toggle($user->id, $product->id));
        $this->assertFalse(Wishlist::isInWishlist($user->id, $product->id));
    }

    // GHDH-UT-015
    public function test_order_can_cancel_only_when_pending_or_confirmed(): void
    {
        foreach (Order::ORDER_STATUSES as $status) {
            $order = new Order(['order_status' => $status]);
            $this->assertSame(in_array($status, ['pending', 'confirmed'], true), $order->canCancel(), $status);
        }
    }

    // GHDH-UT-019
    public function test_order_model_has_no_leftover_controller_method(): void
    {
        $this->assertFalse(method_exists(Order::class, 'show'));
    }

    // GHDH-UT-026
    public function test_cart_clear_removes_all_items(): void
    {
        $item = CartItem::factory()->create();
        $cart = $item->cart;

        $cart->clear();

        $this->assertSame(0, $cart->cartItems()->count());
    }

    // GHDH-UT-027
    public function test_product_final_price_prefers_discount_price(): void
    {
        $this->assertEquals(90000, (new Product(['price' => 120000, 'discount_price' => 90000]))->getFinalPrice());
        $this->assertEquals(120000, (new Product(['price' => 120000, 'discount_price' => null]))->getFinalPrice());
    }

    // HSDC-UT-001, HSDC-UT-026
    public function test_profile_update_request_validates_real_user_columns(): void
    {
        $rules = (new ProfileUpdateRequest())->setUserResolver(fn () => new User(['id' => 1]))->rules();

        $this->assertArrayHasKey('full_name', $rules);
        $this->assertArrayHasKey('phone', $rules);
        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayNotHasKey('name', $rules);
    }

    // HSDC-UT-019
    public function test_shipping_address_rules_are_shared_and_match_column_size(): void
    {
        $data = [
            'receiver_name' => str_repeat('a', 300),
            'phone' => '0901234567',
            'province' => 'Hà Nội',
            'district' => 'Cầu Giấy',
            'ward' => 'Dịch Vọng',
            'address' => '1 Trần Thái Tông',
        ];

        $validator = Validator::make($data, ShippingAddress::validationRules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('receiver_name', $validator->errors()->toArray());
        $this->assertSame('required|string|max:100', ShippingAddress::validationRules()['receiver_name']);
    }

    // HSDC-UT-021
    public function test_set_as_default_keeps_exactly_one_default_address(): void
    {
        $user = User::factory()->create();
        $first = ShippingAddress::factory()->create(['user_id' => $user->id, 'is_default' => true]);
        $second = ShippingAddress::factory()->create(['user_id' => $user->id, 'is_default' => false]);

        $second->setAsDefault();

        $this->assertFalse($first->fresh()->is_default);
        $this->assertTrue($second->fresh()->is_default);
        $this->assertSame(1, ShippingAddress::where('user_id', $user->id)->where('is_default', true)->count());
    }

    // QTA-UT-016, QTA-UT-017, QTA-UT-040
    public function test_order_status_constants_match_database_enum(): void
    {
        $this->assertSame(
            ['pending', 'confirmed', 'processing', 'shipping', 'completed', 'cancelled', 'refunded'],
            Order::ORDER_STATUSES
        );
        $this->assertSame(['unpaid', 'paid', 'refunded'], Order::PAYMENT_STATUSES);
    }

    /**
     * QTA-UT-025..033, KMQC-UT-026..030, QTB-UT-018..026, QTMR-UT-007..009, QTMR-UT-020
     */
    public static function controllerMethodProvider(): array
    {
        return [
            'QTA-UT-025' => [AdminUserController::class, 'store'],
            'QTA-UT-026' => [AdminUserController::class, 'update'],
            'QTA-UT-027' => [AdminUserController::class, 'export'],
            'QTA-UT-028' => [AdminUserController::class, 'getStatistics'],
            'QTA-UT-029' => [AdminUserController::class, 'getActivityLog'],
            'QTA-UT-030' => [AdminUserController::class, 'getOrderHistory'],
            'QTA-UT-031' => [AdminUserController::class, 'resetPassword'],
            'QTA-UT-032' => [AdminProductController::class, 'show'],
            'QTA-UT-033' => [AdminCategoryController::class, 'show'],
            'KMQC-UT-026' => [AdminPromotionController::class, 'show'],
            'KMQC-UT-027' => [AdminPromotionController::class, 'duplicate'],
            'KMQC-UT-028' => [AdminPromotionController::class, 'generateCode'],
            'KMQC-UT-029' => [AdminPromotionController::class, 'getStatistics'],
            'KMQC-UT-030' => [AdminPromotionController::class, 'getUsageReport'],
            'QTB-UT-018' => [AdminReportController::class, 'getDashboardReport'],
            'QTB-UT-019' => [AdminReportController::class, 'getRevenueReport'],
            'QTB-UT-020' => [AdminReportController::class, 'getProductReport'],
            'QTB-UT-021' => [AdminReportController::class, 'getCategoryReport'],
            'QTB-UT-022' => [AdminReportController::class, 'getCustomerReport'],
            'QTB-UT-023' => [AdminReportController::class, 'getInventoryReport'],
            'QTB-UT-024' => [AdminReportController::class, 'getOrderStatusReport'],
            'QTB-UT-025' => [AdminReportController::class, 'getPaymentMethodReport'],
            'QTB-UT-026' => [AdminReportController::class, 'exportReport'],
            'QTMR-UT-007' => [AdminReviewController::class, 'show'],
            'QTMR-UT-008' => [AdminReviewController::class, 'approve'],
            'QTMR-UT-009' => [AdminReviewController::class, 'reject'],
            'QTMR-UT-020' => [AdminContactController::class, 'reply'],
        ];
    }

    #[DataProvider('controllerMethodProvider')]
    public function test_controller_method_used_by_routes_exists(string $controller, string $method): void
    {
        $this->assertTrue(method_exists($controller, $method), "{$controller}::{$method}() không tồn tại");
    }

    // QTA-UT-025..033 và các nhóm route tương tự: mọi route đều trỏ tới method có thật
    public function test_every_registered_route_points_to_an_existing_method(): void
    {
        $missing = [];
        foreach (app('router')->getRoutes() as $route) {
            $action = $route->getActionName();
            if (!str_contains($action, '@')) {
                continue;
            }
            [$class, $method] = explode('@', $action);
            if (!method_exists($class, $method)) {
                $missing[] = $route->uri() . ' -> ' . $action;
            }
        }

        $this->assertSame([], $missing);
    }

    // BUG-035, QTA-UT-023, QTA-UT-039: web và API dùng chung 1 bộ luật validate + cách sinh slug
    public function test_web_and_api_controllers_share_product_and_category_rules(): void
    {
        $sources = [
            'web product' => file_get_contents(app_path('Http/Controllers/Admin/ProductController.php')),
            'api product' => file_get_contents(app_path('Http/Controllers/ProductController.php')),
            'web category' => file_get_contents(app_path('Http/Controllers/Admin/CategoryController.php')),
            'api category' => file_get_contents(app_path('Http/Controllers/CategoryController.php')),
        ];

        foreach ($sources as $name => $code) {
            $model = str_contains($name, 'product') ? 'Product' : 'Category';
            $this->assertSame(2, substr_count($code, "{$model}::validationRules("), "{$name} phải dùng {$model}::validationRules() cho store + update");
        }

        $this->assertSame('required|image|mimes:jpeg,png,jpg,webp|max:2048', \App\Models\Product::validationRules()['image']);
        $this->assertStringStartsWith('nullable', \App\Models\Product::validationRules(5)['image']);
        $this->assertStringEndsWith(',5', \App\Models\Category::validationRules(5)['name']);
    }

    // BUG-035: slug tự sinh không trùng, bỏ qua chính bản ghi đang sửa
    public function test_unique_slug_generation_is_shared(): void
    {
        $existing = Product::factory()->create(['slug' => 'banh-flan']);

        $this->assertSame('banh-flan-1', Product::uniqueSlug('Bánh flan'));
        $this->assertSame('banh-flan', Product::uniqueSlug('Bánh flan', $existing->id));
    }

    // KMQC-UT-039
    public function test_banner_scopes_filter_active_window_position_and_order(): void
    {
        Banner::factory()->create(['position' => 'sidebar', 'status' => 'active', 'display_order' => 2]);
        Banner::factory()->create(['position' => 'sidebar', 'status' => 'active', 'display_order' => 1]);
        Banner::factory()->create(['position' => 'sidebar', 'status' => 'inactive']);
        Banner::factory()->create(['position' => 'sidebar', 'status' => 'active', 'end_date' => now()->subDay()]);
        Banner::factory()->create(['position' => 'footer', 'status' => 'active']);

        $banners = Banner::active()->byPosition('sidebar')->ordered()->get();

        $this->assertCount(2, $banners);
        $this->assertSame([1, 2], $banners->pluck('display_order')->all());
    }

    // QTB-UT-031
    public function test_mark_abandoned_is_scheduled_daily(): void
    {
        $events = collect(app(Schedule::class)->events())
            ->filter(fn ($event) => str_contains($event->command ?? '', 'cart:mark-abandoned'));

        $this->assertCount(1, $events);
        $this->assertSame('0 0 * * *', $events->first()->expression);
    }

    // QTB-UT-031
    public function test_mark_abandoned_only_touches_items_older_than_threshold(): void
    {
        $old = CartItem::factory()->create();
        $old->forceFill(['created_at' => now()->subDays(40)])->save();
        $fresh = CartItem::factory()->create();

        $this->assertSame(1, CartItem::markAbandoned(30));
        $this->assertSame('abandoned', $old->fresh()->status);
        $this->assertSame('active', $fresh->fresh()->status);
    }

    // GHDH-ST-027 (logic nền): đồng bộ giỏ theo giá/tồn kho mới
    public function test_cart_sync_updates_price_and_caps_quantity(): void
    {
        $product = Product::factory()->create(['price' => 150000, 'discount_price' => null, 'stock' => 2]);
        $item = CartItem::factory()->create(['product_id' => $product->id, 'price' => 100000, 'quantity' => 5]);

        $result = $item->cart->syncWithProducts();

        $this->assertNotEmpty($result['updated']);
        $this->assertEquals(150000, $item->fresh()->price);
        $this->assertEquals(2, $item->fresh()->quantity);
    }

    // QTMR-UT-022
    public function test_reviews_table_tracks_reviewer(): void
    {
        $this->assertTrue(Schema::hasColumns('reviews', ['reviewed_by', 'reviewed_at']));
    }

    // HSDC-IT-009 (logic nền)
    public function test_orders_and_addresses_can_outlive_deleted_account(): void
    {
        $user = User::factory()->create();
        $address = ShippingAddress::factory()->create(['user_id' => $user->id]);
        $order = Order::factory()->create(['user_id' => $user->id, 'shipping_address_id' => $address->id]);

        $user->delete();

        $this->assertNull($order->fresh()->user_id);
        $this->assertNull($address->fresh()->user_id);
    }

    // LHTN-IT-020, LHTN-IT-021 (cấu hình): endpoint hỏi-đáp được miễn CSRF cho client ngoài
    public function test_public_message_endpoint_is_excluded_from_csrf(): void
    {
        $middleware = app(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        $excluded = (new \ReflectionMethod($middleware, 'getExcludedPaths'))->invoke($middleware);

        $this->assertContains('api/messages', $excluded);
    }
}
