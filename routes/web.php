<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\PromotionController as AdminPromotionController;
use App\Http\Controllers\Admin\MessageController as AdminMessageController;
use App\Http\Controllers\Admin\ContactController as AdminContactController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\CartAnalyticsController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ShippingAddressController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\Admin\AdminLogController;


/*
|--------------------------------------------------------------------------
| Laravel Default Login Route (REQUIRED)
|--------------------------------------------------------------------------
*/
// Route::get('/login', function () {
//     return redirect('/admin/login');
// })->name('login');
Route::get('/about', [HomeController::class, 'about'])->name('about');
Route::get('/contact', [HomeController::class, 'contact'])->name('contact');
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{id}', [ProductController::class, 'show'])->name('products.show');
Route::get('/register', [RegisterController::class, 'create'])->name('register');
Route::post('/register', [RegisterController::class, 'store']);
/*
|--------------------------------------------------------------------------
| Authenticated User Routes (Dành cho khách đã đăng nhập)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add', [CartController::class, 'addToCart'])->name('cart.add');
    Route::put('/cart/{id}', [CartController::class, 'update'])->whereNumber('id')->name('cart.update');
    Route::delete('/cart/remove/{id}', [CartController::class, 'remove'])->name('cart.remove');
    Route::delete('/cart', [CartController::class, 'clear'])->name('cart.clear');

    // Yêu thích (Wishlist)
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist/toggle', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
    Route::get('/wishlist/count', [WishlistController::class, 'count'])->name('wishlist.count');
    Route::delete('/wishlist', [WishlistController::class, 'clear'])->name('wishlist.clear');
    Route::delete('/wishlist/{productId}', [WishlistController::class, 'remove'])->name('wishlist.remove');

    // Tin nhắn của tôi (xem lại phản hồi của admin cho hỏi-đáp & liên hệ)
    Route::get('/my-messages', [MessageController::class, 'myMessages'])->name('messages.mine');

    Route::post('/momo-payment', [PaymentController::class, 'createPayment'])->name('momo.payment');
    Route::get('/momo-return', [PaymentController::class, 'momoReturn'])->name('momo.return');

    Route::controller(ProfileController::class)->group(function () {
        Route::get('/profile', 'edit')->name('profile.edit');
        Route::patch('/profile', 'updateWeb')->name('profile.update');
        Route::delete('/profile', 'destroy')->name('profile.destroy');

        // Route đổi mật khẩu cho cả User và Admin (dùng chung giao diện hoặc tách ra tùy bạn)
        Route::get('/change-password', 'editPassword')->name('password.edit');
        Route::put('/change-password', 'updatePassword')->name('profile.password.update');
    });

    Route::resource('shipping-addresses', ShippingAddressController::class);
    // 1. Trang hiển thị giao diện Checkout (Cần tạo hàm checkout trong OrderController)
    Route::get('/checkout', [OrderController::class, 'checkout'])->name('checkout.index');
    Route::post('/checkout/cod', [CartController::class, 'storeCOD'])->name('checkout.cod');
    Route::post('/checkout/check-promotion', [CartController::class, 'checkPromotion'])->name('checkout.check-promotion');
    // 2. Xử lý bấm nút "Xác nhận đặt hàng"
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');

    // 3. Xem danh sách đơn hàng của tôi
    Route::get('/my-orders', [OrderController::class, 'myOrders'])->name('orders.my-orders');

    // 4. Xem chi tiết một đơn hàng
    Route::get('/orders/{id}', [OrderController::class, 'show'])->name('orders.show');

    // 5. Hủy đơn hàng
    Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
});
/*
|--------------------------------------------------------------------------
| Public API – khách hàng gửi tin nhắn (không cần auth)
|--------------------------------------------------------------------------
*/
// Nằm ở web.php để nhận diện được khách đã đăng nhập qua session; được miễn CSRF (bootstrap/app.php)
// để client ngoài vẫn gọi được như 1 API công khai, và giới hạn tần suất chống spam.
Route::post('/api/messages', [MessageController::class, 'store'])
    ->middleware('throttle:message-form')
    ->name('api.messages.store');

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {

    // Guest routes (not logged in)
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'login']);
    });

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

        // Products - thao tác nhanh trên bảng danh sách
        Route::put('products/{id}/status', [ProductController::class, 'updateStatus'])->name('products.update-status');
        Route::post('products/{id}/duplicate', [ProductController::class, 'duplicate'])->name('products.duplicate');

        // Products
        Route::resource('products', AdminProductController::class)->names([
            'index'   => 'products.index',
            'create'  => 'products.create',
            'store'   => 'products.store',
            'edit'    => 'products.edit',
            'update'  => 'products.update',
            'destroy' => 'products.destroy',
        ]);

        // Categories - sắp xếp thứ tự hiển thị (khai báo trước resource để không bị /{category} "nuốt")
        Route::put('categories/update-order', [CategoryController::class, 'updateOrder'])->name('categories.update-order');

        // Categories
        Route::resource('categories', AdminCategoryController::class)->names([
            'index'   => 'categories.index',
            'create'  => 'categories.create',
            'store'   => 'categories.store',
            'edit'    => 'categories.edit',
            'update'  => 'categories.update',
            'destroy' => 'categories.destroy',
        ]);

        // Orders
        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/',             [AdminOrderController::class, 'index'])->name('index');
            Route::get('/{id}',         [AdminOrderController::class, 'show'])->name('show');
            Route::put('/{id}/status',  [AdminOrderController::class, 'updateStatus'])->name('update-status');
            Route::put('/{id}/payment', [AdminOrderController::class, 'updatePaymentStatus'])->name('update-payment');
            Route::delete('/{id}',      [AdminOrderController::class, 'destroy'])->name('destroy');
            Route::post('/mark-read',   [AdminOrderController::class, 'markRead'])->name('mark-read');
        });

        // Messages
        Route::prefix('messages')->name('messages.')->group(function () {
            Route::get('/',                    [AdminMessageController::class, 'index'])->name('index');
            Route::get('/{id}',                [AdminMessageController::class, 'show'])->name('show');
            Route::post('/{id}/reply',         [AdminMessageController::class, 'reply'])->name('reply');
            Route::delete('/{id}',             [AdminMessageController::class, 'destroy'])->name('destroy');
            Route::post('/mark-all-read',      [AdminMessageController::class, 'markAllRead'])->name('mark-all-read');
        });

        // Contacts (liên hệ gửi qua form "Liên hệ với chúng tôi")
        Route::prefix('contacts')->name('contacts.')->group(function () {
            Route::get('/',             [AdminContactController::class, 'index'])->name('index');
            Route::get('/{id}',         [AdminContactController::class, 'show'])->name('show');
            Route::post('/{id}/reply',  [AdminContactController::class, 'reply'])->name('reply');
            Route::delete('/{id}',      [AdminContactController::class, 'destroy'])->name('destroy');
        });

        // Promotions
        Route::resource('promotions', AdminPromotionController::class)->names([
            'index'   => 'promotions.index',
            'create'  => 'promotions.create',
            'store'   => 'promotions.store',
            'edit'    => 'promotions.edit',
            'update'  => 'promotions.update',
            'destroy' => 'promotions.destroy',
        ]);
        Route::post('promotions/{id}/toggle-status', [AdminPromotionController::class, 'toggleStatus'])->name('promotions.toggle-status');
        Route::post('promotions/check-code',         [AdminPromotionController::class, 'checkCode'])->name('promotions.check-code');
        Route::get('promotions-generate-code',       [AdminPromotionController::class, 'generateCode'])->name('promotions.generate-code');
        Route::get('promotions-statistics',          [AdminPromotionController::class, 'getStatistics'])->name('promotions.statistics');
        Route::post('promotions/{id}/duplicate',     [AdminPromotionController::class, 'duplicate'])->name('promotions.duplicate');
        Route::get('promotions/{id}/usage-report',   [AdminPromotionController::class, 'getUsageReport'])->name('promotions.usage-report');

        // Reports
        Route::get('reports', [AdminReportController::class, 'index'])->name('reports.index');
        Route::get('reports/export', [AdminReportController::class, 'exportReport'])->name('reports.export');
        Route::get('reports/inventory', [AdminReportController::class, 'getInventoryReport'])->name('reports.inventory');
        Route::get('reports/revenue', [AdminReportController::class, 'getRevenueReport'])->name('reports.revenue');
        Route::get('reports/products', [AdminReportController::class, 'getProductReport'])->name('reports.products');
        Route::get('reports/categories', [AdminReportController::class, 'getCategoryReport'])->name('reports.categories');
        Route::get('reports/customers', [AdminReportController::class, 'getCustomerReport'])->name('reports.customers');
        Route::get('reports/order-status', [AdminReportController::class, 'getOrderStatusReport'])->name('reports.order-status');
        Route::get('reports/payment-methods', [AdminReportController::class, 'getPaymentMethodReport'])->name('reports.payment-methods');

        // Nhật ký hoạt động quản trị
        Route::prefix('logs')->name('logs.')->group(function () {
            Route::get('/', [AdminLogController::class, 'index'])->name('index');
            Route::get('/statistics', [AdminLogController::class, 'getStatistics'])->name('statistics');
            Route::get('/recent', [AdminLogController::class, 'getRecentActivities'])->name('recent');
            Route::get('/by-record', [AdminLogController::class, 'getByRecord'])->name('by-record');
            Route::post('/clear-old', [AdminLogController::class, 'clearOldLogs'])->name('clear-old');
            Route::get('/export', [AdminLogController::class, 'export'])->name('export');
        });

        // Users
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/',            [AdminUserController::class, 'index'])->name('index');
            Route::get('/export',      [AdminUserController::class, 'export'])->name('export');
            Route::get('/statistics',  [AdminUserController::class, 'getStatistics'])->name('statistics');
            Route::post('/{id}/reset-password', [AdminUserController::class, 'resetPassword'])->name('reset-password');
            Route::get('/{id}/activity-log',    [AdminUserController::class, 'getActivityLog'])->name('activity-log');
            Route::get('/{id}/orders',          [AdminUserController::class, 'getOrderHistory'])->name('order-history');
            Route::get('/{id}',        [AdminUserController::class, 'show'])->name('show');
            Route::put('/{id}/status', [AdminUserController::class, 'updateStatus'])->name('update-status');
            Route::delete('/{id}',     [AdminUserController::class, 'destroy'])->name('destroy');
        });

        // Reviews
        Route::prefix('reviews')->name('reviews.')->group(function () {
            Route::get('/', [AdminReviewController::class, 'index'])->name('index');
            Route::get('/{id}', [AdminReviewController::class, 'show'])->name('show');
            Route::put('/{id}/approve', [AdminReviewController::class, 'approve'])->name('approve');
            Route::put('/{id}/reject', [AdminReviewController::class, 'reject'])->name('reject');
            Route::put('/{id}/status', [AdminReviewController::class, 'updateStatus'])->name('update-status');
            Route::delete('/{id}', [AdminReviewController::class, 'destroy'])->name('destroy');
        });

        // Cart Analytics
        Route::prefix('cart-analytics')->name('cart-analytics.')->group(function () {
            Route::get('/', [CartAnalyticsController::class, 'index'])->name('index');
            Route::post('/mark-abandoned', [CartAnalyticsController::class, 'markAbandoned'])->name('mark-abandoned');
            Route::get('/suggest-discounts', [CartAnalyticsController::class, 'suggestDiscounts'])->name('suggest-discounts');
        });
    });

    // Redirect /admin
    Route::get('/', function () {
        return auth()->check() && auth()->user()->role === 'admin'
            ? redirect()->route('admin.dashboard')
            : redirect()->route('admin.login');
    });
});

/*
|--------------------------------------------------------------------------
| Root redirect
|--------------------------------------------------------------------------
*/
// Route::get('/', function () {
//     return redirect('/home');
// });

require __DIR__.'/auth.php';