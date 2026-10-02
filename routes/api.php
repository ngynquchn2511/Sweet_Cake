<?php
// routes/api.php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Admin Controllers
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\PromotionController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\ReportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes (không cần auth)
Route::get('/test', function () {
    return response()->json(['message' => 'API is working!']);
});

// MoMo gọi server-to-server (IPN) - không có session/CSRF nên phải nằm ở nhóm api, không phải web
Route::post('/momo-ipn', [App\Http\Controllers\PaymentController::class, 'momoIPN']);

// Auth routes (Login, Register, etc.)
Route::prefix('auth')->group(function () {
    Route::post('/login', [App\Http\Controllers\Auth\AuthController::class, 'login']);
    Route::post('/register', [App\Http\Controllers\Auth\AuthController::class, 'register']);
    Route::post('/forgot-password', [App\Http\Controllers\Auth\AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [App\Http\Controllers\Auth\AuthController::class, 'resetPassword']);
    
    // Protected auth routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [App\Http\Controllers\Auth\AuthController::class, 'logout']);
        Route::get('/me', [App\Http\Controllers\Auth\AuthController::class, 'me']);
        Route::put('/update-profile', [App\Http\Controllers\Auth\AuthController::class, 'updateProfile']);
        Route::put('/change-password', [App\Http\Controllers\Auth\AuthController::class, 'changePassword']);
    });
});

/*
|--------------------------------------------------------------------------
| ADMIN ROUTES
|--------------------------------------------------------------------------
| Prefix: /api/admin
| Middleware: auth:sanctum, admin
*/

// Tên route có tiền tố "api." để không đè lên route web cùng tên (vd admin.products.index)
Route::prefix('admin')->name('api.')->middleware(['auth:sanctum', 'admin'])->group(function () {
    
    // ========================================
    // DASHBOARD
    // ========================================
    Route::prefix('dashboard')->name('admin.dashboard.')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('index');
        Route::get('/stats-by-date-range', [DashboardController::class, 'getStatsByDateRange'])->name('stats-by-date');
        Route::get('/order-stats', [DashboardController::class, 'getOrderStatsByStatus'])->name('order-stats');
        Route::get('/sales-by-category', [DashboardController::class, 'getSalesByCategory'])->name('sales-by-category');
    });

    // ========================================
    // USERS (CUSTOMERS) MANAGEMENT
    // ========================================
    Route::prefix('users')->name('admin.users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/statistics', [UserController::class, 'getStatistics'])->name('statistics');
        Route::get('/{id}', [UserController::class, 'show'])->name('show');
        Route::put('/{id}', [UserController::class, 'update'])->name('update');
        Route::delete('/{id}', [UserController::class, 'destroy'])->name('destroy');
        Route::put('/{id}/status', [UserController::class, 'updateStatus'])->name('update-status');
        Route::post('/{id}/reset-password', [UserController::class, 'resetPassword'])->name('reset-password');
        Route::get('/{id}/orders', [UserController::class, 'getOrderHistory'])->name('order-history');
        Route::get('/{id}/activity-log', [UserController::class, 'getActivityLog'])->name('activity-log');
        Route::post('/export', [UserController::class, 'export'])->name('export');
    });

    // ========================================
    // PRODUCTS MANAGEMENT
    // ========================================
    Route::prefix('products')->name('admin.products.')->group(function () {
        Route::get('/', [App\Http\Controllers\ProductController::class, 'index'])->name('index');
        Route::post('/', [App\Http\Controllers\ProductController::class, 'store'])->name('store');
        Route::get('/{id}', [App\Http\Controllers\ProductController::class, 'show'])->name('show');
        Route::put('/{id}', [App\Http\Controllers\ProductController::class, 'update'])->name('update');
        Route::delete('/{id}', [App\Http\Controllers\ProductController::class, 'destroy'])->name('destroy');
        Route::put('/{id}/status', [App\Http\Controllers\ProductController::class, 'updateStatus'])->name('update-status');
        Route::post('/{id}/duplicate', [App\Http\Controllers\ProductController::class, 'duplicate'])->name('duplicate');
    });

    // ========================================
    // CATEGORIES MANAGEMENT
    // ========================================
    Route::prefix('categories')->name('admin.categories.')->group(function () {
        Route::get('/', [App\Http\Controllers\CategoryController::class, 'index'])->name('index');
        Route::post('/', [App\Http\Controllers\CategoryController::class, 'store'])->name('store');
        // Phải khai báo TRƯỚC /{id}, nếu không PUT /update-order sẽ bị route /{id} "nuốt" mất (id = "update-order")
        Route::put('/update-order', [App\Http\Controllers\CategoryController::class, 'updateOrder'])->name('update-order');
        Route::get('/{id}', [App\Http\Controllers\CategoryController::class, 'show'])->name('show');
        Route::put('/{id}', [App\Http\Controllers\CategoryController::class, 'update'])->name('update');
        Route::delete('/{id}', [App\Http\Controllers\CategoryController::class, 'destroy'])->name('destroy');
        Route::put('/{id}/status', [App\Http\Controllers\CategoryController::class, 'updateStatus'])->name('update-status');
    });

    // ========================================
    // ORDERS MANAGEMENT
    // ========================================
    Route::prefix('orders')->name('admin.orders.')->group(function () {
        Route::get('/', [App\Http\Controllers\OrderController::class, 'index'])->name('index');
        Route::get('/statistics', [App\Http\Controllers\OrderController::class, 'getStatistics'])->name('statistics');
        Route::get('/{id}', [App\Http\Controllers\OrderController::class, 'show'])->name('show');
        Route::put('/{id}/status', [App\Http\Controllers\OrderController::class, 'updateStatus'])->name('update-status');
        Route::put('/{id}/cancel', [App\Http\Controllers\OrderController::class, 'cancel'])->name('cancel');
        Route::post('/export', [App\Http\Controllers\OrderController::class, 'export'])->name('export');
    });

    // ========================================
    // PROMOTIONS MANAGEMENT
    // ========================================
    Route::prefix('promotions')->name('admin.promotions.')->group(function () {
        Route::get('/', [PromotionController::class, 'index'])->name('index');
        Route::post('/', [PromotionController::class, 'store'])->name('store');
        Route::get('/statistics', [PromotionController::class, 'getStatistics'])->name('statistics');
        Route::post('/check-code', [PromotionController::class, 'checkCode'])->name('check-code');
        Route::get('/generate-code', [PromotionController::class, 'generateCode'])->name('generate-code');
        Route::get('/{id}', [PromotionController::class, 'show'])->name('show');
        Route::put('/{id}', [PromotionController::class, 'update'])->name('update');
        Route::delete('/{id}', [PromotionController::class, 'destroy'])->name('destroy');
        Route::put('/{id}/toggle-status', [PromotionController::class, 'toggleStatus'])->name('toggle-status');
        Route::get('/{id}/usage-report', [PromotionController::class, 'getUsageReport'])->name('usage-report');
        Route::post('/{id}/duplicate', [PromotionController::class, 'duplicate'])->name('duplicate');
    });

    // ========================================
    // BANNERS MANAGEMENT
    // ========================================
    Route::prefix('banners')->name('admin.banners.')->group(function () {
        Route::get('/', [BannerController::class, 'index'])->name('index');
        Route::post('/', [BannerController::class, 'store'])->name('store');
        Route::get('/statistics', [BannerController::class, 'getStatistics'])->name('statistics');
        Route::get('/positions', [BannerController::class, 'getPositions'])->name('positions');
        Route::get('/position/{position}', [BannerController::class, 'getByPosition'])->name('by-position');
        Route::get('/active', [BannerController::class, 'getActive'])->name('active');
        Route::get('/{id}', [BannerController::class, 'show'])->name('show');
        Route::post('/{id}', [BannerController::class, 'update'])->name('update'); // POST vì có upload file
        Route::delete('/{id}', [BannerController::class, 'destroy'])->name('destroy');
        Route::put('/{id}/toggle-status', [BannerController::class, 'toggleStatus'])->name('toggle-status');
        Route::put('/update-order', [BannerController::class, 'updateOrder'])->name('update-order');
        Route::post('/{id}/duplicate', [BannerController::class, 'duplicate'])->name('duplicate');
    });

    // ========================================
    // REPORTS
    // ========================================
    Route::prefix('reports')->name('admin.reports.')->group(function () {
        Route::get('/revenue', [ReportController::class, 'getRevenueReport'])->name('revenue');
        Route::get('/products', [ReportController::class, 'getProductReport'])->name('products');
        Route::get('/customers', [ReportController::class, 'getCustomerReport'])->name('customers');
        Route::get('/categories', [ReportController::class, 'getCategoryReport'])->name('categories');
        Route::get('/order-status', [ReportController::class, 'getOrderStatusReport'])->name('order-status');
        Route::get('/payment-methods', [ReportController::class, 'getPaymentMethodReport'])->name('payment-methods');
        Route::get('/inventory', [ReportController::class, 'getInventoryReport'])->name('inventory');
        Route::get('/dashboard', [ReportController::class, 'getDashboardReport'])->name('dashboard');
        Route::post('/export', [ReportController::class, 'exportReport'])->name('export');
    });

    // ========================================
    // REVIEWS MANAGEMENT (Optional - nếu cần)
    // ========================================
    Route::prefix('reviews')->name('admin.reviews.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\ReviewController::class, 'index'])->name('index');
        Route::get('/{id}', [App\Http\Controllers\Admin\ReviewController::class, 'show'])->name('show');
        Route::put('/{id}/approve', [App\Http\Controllers\Admin\ReviewController::class, 'approve'])->name('approve');
        Route::put('/{id}/reject', [App\Http\Controllers\Admin\ReviewController::class, 'reject'])->name('reject');
        Route::delete('/{id}', [App\Http\Controllers\Admin\ReviewController::class, 'destroy'])->name('destroy');
    });

    // ========================================
    // ADMIN LOGS
    // ========================================
    Route::prefix('logs')->name('admin.logs.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\AdminLogController::class, 'index'])->name('index');
        Route::get('/statistics', [App\Http\Controllers\Admin\AdminLogController::class, 'getStatistics'])->name('statistics');
        Route::get('/recent', [App\Http\Controllers\Admin\AdminLogController::class, 'getRecentActivities'])->name('recent');
        Route::get('/by-record', [App\Http\Controllers\Admin\AdminLogController::class, 'getByRecord'])->name('by-record');
        Route::post('/clear-old', [App\Http\Controllers\Admin\AdminLogController::class, 'clearOldLogs'])->name('clear-old');
        Route::get('/export', [App\Http\Controllers\Admin\AdminLogController::class, 'export'])->name('export');
        Route::get('/{id}', [App\Http\Controllers\Admin\AdminLogController::class, 'show'])->name('show');
    });
});

/*
|--------------------------------------------------------------------------
| CUSTOMER ROUTES (Frontend Customer)
|--------------------------------------------------------------------------
| Prefix: /api
| Middleware: auth:sanctum (một số routes)
*/

Route::prefix('customer')->group(function () {
    
    // Public routes
    // Route::get('/products', [App\Http\Controllers\ProductController::class, 'index']);
    Route::get('/products', [App\Http\Controllers\ProductController::class, 'index']);
    Route::get('/products/{slug}', [App\Http\Controllers\ProductController::class, 'show']);
    Route::get('/products/{productId}/reviews', [App\Http\Controllers\ReviewController::class, 'getProductReviews']);
    Route::get('/categories', [App\Http\Controllers\CategoryController::class, 'index']);
    Route::get('/banners', [BannerController::class, 'getActive']);
    Route::post('/contact', [App\Http\Controllers\ContactController::class, 'store'])->middleware('throttle:contact-form');

    // Protected routes (cần login)
    Route::middleware('auth:sanctum')->group(function () {
        
        // Profile
        Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'show']);
        Route::put('/profile', [App\Http\Controllers\ProfileController::class, 'update']);
        
        // Shipping Addresses
        Route::get('/shipping-addresses', [App\Http\Controllers\ProfileController::class, 'getShippingAddresses']);
        Route::post('/shipping-addresses', [App\Http\Controllers\ProfileController::class, 'storeShippingAddress']);
        Route::put('/shipping-addresses/{id}', [App\Http\Controllers\ProfileController::class, 'updateShippingAddress']);
        Route::delete('/shipping-addresses/{id}', [App\Http\Controllers\ProfileController::class, 'deleteShippingAddress']);
        Route::put('/shipping-addresses/{id}/set-default', [App\Http\Controllers\ProfileController::class, 'setDefaultAddress']);
        
        // Cart
        Route::get('/cart', [App\Http\Controllers\CartController::class, 'index']);
        Route::post('/cart/add', [App\Http\Controllers\CartController::class, 'add']);
        Route::put('/cart/{id}', [App\Http\Controllers\CartController::class, 'update']);
        Route::delete('/cart/{id}', [App\Http\Controllers\CartController::class, 'remove']);
        Route::delete('/cart', [App\Http\Controllers\CartController::class, 'clear']);
        Route::get('/cart/count', [App\Http\Controllers\CartController::class, 'count']);
        Route::post('/cart/sync', [App\Http\Controllers\CartController::class, 'sync']);
        Route::post('/cart/check-promotion', [App\Http\Controllers\CartController::class, 'checkPromotion']);
        
        // Orders
        Route::get('/orders', [App\Http\Controllers\OrderController::class, 'myOrders']);
        Route::post('/orders', [App\Http\Controllers\OrderController::class, 'store']);
        Route::get('/orders/{id}', [App\Http\Controllers\OrderController::class, 'show']);
        Route::put('/orders/{id}/cancel', [App\Http\Controllers\OrderController::class, 'cancel']);
        
        // Wishlist
        Route::get('/wishlist', [App\Http\Controllers\WishlistController::class, 'index']);
        Route::post('/wishlist/toggle', [App\Http\Controllers\WishlistController::class, 'toggle']);
        Route::post('/wishlist/check', [App\Http\Controllers\WishlistController::class, 'check']);
        Route::delete('/wishlist/clear', [App\Http\Controllers\WishlistController::class, 'clear']);
        Route::get('/wishlist/count', [App\Http\Controllers\WishlistController::class, 'count']);
        Route::delete('/wishlist/{productId}', [App\Http\Controllers\WishlistController::class, 'remove']);

        // Reviews
        Route::post('/reviews', [App\Http\Controllers\ReviewController::class, 'store']);
        Route::put('/reviews/{id}', [App\Http\Controllers\ReviewController::class, 'update']);
        Route::delete('/reviews/{id}', [App\Http\Controllers\ReviewController::class, 'destroy']);
        Route::get('/reviews/mine', [App\Http\Controllers\ReviewController::class, 'getUserReviews']);

        // Tin nhắn của tôi (xem lại phản hồi của admin)
        Route::get('/messages', [App\Http\Controllers\MessageController::class, 'myMessages']);
    });
});
