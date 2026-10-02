<?php
// app/Http/Controllers/WishlistController.php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    /**
     * Get user's wishlist
     */
    public function index(Request $request)
    {
        $wishlist = Wishlist::where('user_id', Auth::id())
            ->with(['product' => function($query) {
                $query->select('id', 'name', 'slug', 'price', 'discount_price', 'image', 'stock', 'status');
            }])
            ->latest()
            ->get();

        if (!$request->wantsJson() && !$request->is('api/*')) {
            return \Inertia\Inertia::render('Wishlist/Index', [
                'items' => $wishlist,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $wishlist
        ]);
    }

    /**
     * Toggle product in wishlist (add/remove)
     */
    public function toggle(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $userId = Auth::id();
        $productId = $request->product_id;

        // Check if product exists and is active
        $product = Product::find($productId);
        if (!$product || $product->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Sản phẩm không tồn tại hoặc không khả dụng'
            ], 404);
        }

        // Toggle wishlist
        $isAdded = Wishlist::toggle($userId, $productId);

        return response()->json([
            'success' => true,
            'message' => $isAdded ? 'Đã thêm vào yêu thích' : 'Đã xóa khỏi yêu thích',
            'is_in_wishlist' => $isAdded
        ]);
    }

    /**
     * Check if product is in wishlist
     */
    public function check(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $isInWishlist = Wishlist::isInWishlist(Auth::id(), $request->product_id);

        return response()->json([
            'success' => true,
            'is_in_wishlist' => $isInWishlist
        ]);
    }

    /**
     * Remove product from wishlist
     */
    public function remove($productId)
    {
        $wishlist = Wishlist::where('user_id', Auth::id())
            ->where('product_id', $productId)
            ->first();

        if (!$wishlist) {
            return response()->json([
                'success' => false,
                'message' => 'Sản phẩm không có trong danh sách yêu thích'
            ], 404);
        }

        $wishlist->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa khỏi danh sách yêu thích'
        ]);
    }

    /**
     * Clear all wishlist items
     */
    public function clear()
    {
        Wishlist::where('user_id', Auth::id())->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa toàn bộ danh sách yêu thích'
        ]);
    }

    /**
     * Get wishlist count
     */
    public function count()
    {
        $count = Wishlist::where('user_id', Auth::id())->count();

        return response()->json([
            'success' => true,
            'count' => $count
        ]);
    }
}