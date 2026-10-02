<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CartAnalyticsController extends Controller
{
    /**
     * Hiển thị thống kê giỏ hàng bị bỏ
     */
    public function index()
    {
        // Sản phẩm bị bỏ giỏ nhiều nhất (status = abandoned)
        $abandonedProducts = Product::select('products.id', 'products.name', 'products.image', 'products.price', 'products.discount_price')
            ->selectRaw('COUNT(cart_items.id) as abandoned_count')
            ->selectRaw('SUM(cart_items.quantity) as total_quantity_abandoned')
            ->join('cart_items', 'products.id', '=', 'cart_items.product_id')
            ->where('cart_items.status', 'abandoned')
            ->groupBy('products.id', 'products.name', 'products.image', 'products.price', 'products.discount_price')
            ->orderByDesc('abandoned_count')
            ->limit(20)
            ->get();

        // Sản phẩm được thêm vào giỏ nhưng chưa mua (status = active, created > 7 days ago)
        $inactiveCartProducts = Product::select('products.id', 'products.name', 'products.image', 'products.price', 'products.discount_price')
            ->selectRaw('COUNT(cart_items.id) as inactive_count')
            ->selectRaw('SUM(cart_items.quantity) as total_quantity_inactive')
            ->join('cart_items', 'products.id', '=', 'cart_items.product_id')
            ->where('cart_items.status', 'active')
            ->where('cart_items.created_at', '<', now()->subDays(7))
            ->groupBy('products.id', 'products.name', 'products.image', 'products.price', 'products.discount_price')
            ->orderByDesc('inactive_count')
            ->limit(20)
            ->get();

        // Thống kê 3 card mới
        $stats = [
            // Card 1: Số sản phẩm unique đang trong giỏ (chưa mua)
            'total_products_in_cart' => CartItem::where('status', 'active')
                ->distinct('product_id')
                ->count('product_id'),

            // Card 2: Số sản phẩm unique nằm giỏ > 7 ngày
            'products_in_cart_over_7days' => CartItem::where('status', 'active')
                ->where('created_at', '<', now()->subDays(7))
                ->distinct('product_id')
                ->count('product_id'),

            // Card 3: Tổng giá trị (tiền) của tất cả sản phẩm đang trong giỏ (chưa mua)
            'total_value_in_cart' => CartItem::where('status', 'active')
                ->selectRaw('SUM(price * quantity) as total_value')
                ->value('total_value') ?? 0,
        ];

        return Inertia::render('Admin/CartAnalytics/Index', [
            'abandonedProducts'     => $abandonedProducts,
            'inactiveCartProducts'  => $inactiveCartProducts,
            'stats'                 => $stats,
        ]);
    }

    /**
     * Đánh dấu cart items cũ thành abandoned
     */
    public function markAbandoned()
    {
        $updated = CartItem::markAbandoned();

        return response()->json([
            'success' => true,
            'message' => "Đã đánh dấu {$updated} cart items thành abandoned",
        ]);
    }

    /**
     * Gợi ý giảm giá cho sản phẩm bị bỏ giỏ nhiều
     */
    public function suggestDiscounts()
    {
        $suggestions = Product::select('products.id', 'products.name', 'products.price', 'products.discount_price')
            ->selectRaw('COUNT(cart_items.id) as abandoned_count')
            ->join('cart_items', 'products.id', '=', 'cart_items.product_id')
            ->where('cart_items.status', 'abandoned')
            ->groupBy('products.id', 'products.name', 'products.price', 'products.discount_price')
            ->havingRaw('COUNT(cart_items.id) > 10')
            ->whereNull('products.discount_price')
            ->orderByDesc('abandoned_count')
            ->get()
            ->map(function ($product) {
                $discountPercent = min(20, 10 + ($product->abandoned_count - 10));
                $suggestedPrice = $product->price * (1 - $discountPercent / 100);
                
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'current_price' => $product->price,
                    'suggested_discount_price' => round($suggestedPrice, -3),
                    'discount_percent' => $discountPercent,
                    'abandoned_count' => $product->abandoned_count,
                    'reason' => "Bị bỏ giỏ {$product->abandoned_count} lần",
                ];
            });

        return response()->json([
            'success' => true,
            'suggestions' => $suggestions,
        ]);
    }
}