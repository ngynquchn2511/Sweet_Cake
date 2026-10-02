<?php
// app/Http/Controllers/CartController.php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Models\Order;      // <--- PHẢI NẰM NGOÀI CLASS
use App\Models\OrderItem;
use App\Models\ShippingAddress;
use Illuminate\Support\Str; // Quan trọng: Để tạo chuỗi ngẫu nhiên
use Illuminate\Validation\ValidationException;
use App\Services\OrderPlacementService;


class CartController extends Controller
{
    /**
     * Get user's cart
     */
    public function index()
    {
        // 1. Lấy giỏ hàng của user
        $cart = $this->getUserCart();

        // 2. Nếu chưa có giỏ hàng, trả về mảng rỗng qua Inertia (Không dùng response()->json)
        if (!$cart) {
            return Inertia::render('Cart/Index', [
                'cartItems' => [],
                'totals' => [
                    'subtotal' => 0,
                    'total_items' => 0,
                ],
                'syncNotice' => null,
            ]);
        }

        // 3. Đồng bộ giá/tồn kho mới nhất trước khi hiển thị (admin có thể đã đổi giá/hạ tồn kho)
        $sync = $cart->syncWithProducts();

        // 4. Eager Load để lấy thông tin sản phẩm (Tránh lỗi N+1 query)
        $cart->load(['cartItems.product' => function ($query) {
            $query->select('id', 'name', 'slug', 'price', 'discount_price', 'image', 'stock', 'status');
        }]);

        // 5. Trả dữ liệu về giao diện Cart/Index
        return Inertia::render('Cart/Index', [
            // Lấy danh sách item đã load kèm product
            'cartItems' => $cart->cartItems,
            'totals' => [
                'subtotal' => $cart->getSubtotal(),
                'total_items' => $cart->getTotalItems(),
            ],
            'syncNotice' => ($sync['removed'] || $sync['updated'])
                ? 'Giỏ hàng đã được cập nhật theo giá và tồn kho mới nhất.'
                : null,
        ]);
    }


    public function storeCOD(Request $request, OrderPlacementService $placement)
    {
        $request->validate([
            'promotion_code' => 'nullable|string|max:50',
            'shipping_address_id' => 'nullable|integer',
        ]);

        try {
            $placement->placeFromCart(
                auth()->id(),
                'cod',
                $request->shipping_address_id,
                $request->promotion_code
            );
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
        }

        return redirect()->route('dashboard')->with('success', 'Đặt hàng thành công!');
    }
    /**
     * Add product to cart
     */
    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::find($request->product_id);

        // Check if product is available
        if (!$product || $product->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Sản phẩm không khả dụng'
            ], 422);
        }

        // Check stock
        if ($product->stock < $request->quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Số lượng sản phẩm không đủ. Còn lại: ' . $product->stock
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Get or create cart
            $cart = $this->getUserCart();
            if (!$cart) {
                $cart = Cart::create(['user_id' => Auth::id()]);
            }

            // Get current price (discount price if available)
            $price = $product->getFinalPrice();

            // Check if item already in cart
            $cartItem = $cart->cartItems()->where('product_id', $product->id)->first();

            if ($cartItem) {
                // Update quantity
                $newQuantity = $cartItem->quantity + $request->quantity;

                // Check stock again
                if ($product->stock < $newQuantity) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Số lượng vượt quá tồn kho. Còn lại: ' . $product->stock
                    ], 422);
                }

                $cartItem->update([
                    'quantity' => $newQuantity,
                    'price' => $price, // Update price in case it changed
                ]);
            } else {
                // Add new item
                $cartItem = $cart->cartItems()->create([
                    'product_id' => $product->id,
                    'quantity' => $request->quantity,
                    'price' => $price,
                ]);
            }

            DB::commit();

            $cartItem->load('product');

            return response()->json([
                'success' => true,
                'message' => 'Đã thêm vào giỏ hàng',
                'data' => $cartItem
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update cart item quantity
     */
    public function update(Request $request, $cartItemId)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = $this->getUserCart(); // Đảm bảo hàm này trả về model Cart của User
        if (!$cart) {
            return redirect()->back()->with('error', 'Giỏ hàng không tồn tại');
        }

        $cartItem = $cart->cartItems()->findOrFail($cartItemId);
        $product = $cartItem->product;

        // Kiểm tra tồn kho
        if ($product->stock < $request->quantity) {
            return redirect()->back()->with('error', "Sản phẩm chỉ còn {$product->stock} chiếc.");
        }

        $cartItem->update([
            'quantity' => $request->quantity,
            'price' => $product->discount_price ?? $product->price,
        ]);

        // Trả về redirect để Inertia cập nhật lại props cartItems và totals
        return redirect()->back()->with('success', 'Cập nhật số lượng thành công!');
    }

    /**
     * Remove item from cart
     */
    public function remove(Request $request, $id)
{
    try {
        // Chỉ được xoá sản phẩm nằm trong chính giỏ hàng của mình
        $cart = $this->getUserCart();
        $cartItem = $cart ? $cart->cartItems()->findOrFail($id) : null;

        if (!$cartItem) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
        }

        $cartItem->delete();

        // Với Inertia, nên dùng redirect thay vì trả về JSON success
        return redirect()->back()->with('message', 'Đã xóa sản phẩm khỏi giỏ hàng');

    } catch (\Exception $e) {
        return redirect()->back()->withErrors(['message' => 'Không tìm thấy sản phẩm để xóa']);
    }
}

    /**
     * Clear all cart items
     */
    public function clear()
    {
        $cart = $this->getUserCart();
        if (!$cart) {
            if (!request()->is('api/*') && !request()->wantsJson()) {
                return redirect()->back();
            }
            return response()->json([
                'success' => false,
                'message' => 'Giỏ hàng không tồn tại'
            ], 404);
        }

        $cart->clear();

        if (!request()->is('api/*') && !request()->wantsJson()) {
            return redirect()->back()->with('success', 'Đã xóa toàn bộ giỏ hàng');
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa toàn bộ giỏ hàng'
        ]);
    }

    /**
     * Get cart count (total items)
     */
    public function count()
    {
        $cart = $this->getUserCart();
        $count = $cart ? $cart->getTotalItems() : 0;

        return response()->json([
            'success' => true,
            'count' => $count
        ]);
    }

    /**
     * Sync cart (update prices and check stock for all items)
     */
    public function sync()
    {
        $cart = $this->getUserCart();
        if (!$cart) {
            return response()->json([
                'success' => true,
                'data' => [
                    'items' => [],
                    'removed_items' => [],
                    'updated_items' => [],
                ]
            ]);
        }

        $sync = $cart->syncWithProducts();
        $removedItems = $sync['removed'];
        $updatedItems = $sync['updated'];

        $cart->load(['cartItems.product']);

        return response()->json([
            'success' => true,
            'message' => 'Đã đồng bộ giỏ hàng',
            'data' => [
                'items' => $cart->cartItems,
                'removed_items' => $removedItems,
                'updated_items' => $updatedItems,
                'subtotal' => $cart->getSubtotal(),
                'total_items' => $cart->getTotalItems(),
            ]
        ]);
    }

    /**
     * Kiểm tra mã khuyến mãi trước khi đặt hàng, dựa trên tổng tiền giỏ hàng hiện tại.
     */
    public function checkPromotion(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $cart = $this->getUserCart();
        $subtotal = $cart ? $cart->getSubtotal() : 0;

        $result = \App\Models\Promotion::validateAndCalculate($request->code, (float) $subtotal);

        return response()->json([
            'success' => $result['valid'],
            'message' => $result['message'],
            'discount_amount' => $result['discount_amount'],
        ]);
    }

    /**
     * Helper: Get current user's cart
     */
    private function getUserCart()
    {
        return Cart::where('user_id', Auth::id())->first();
    }
    public function addToCart(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        // 1. Lấy thông tin sản phẩm để lấy giá
        $product = \App\Models\Product::findOrFail($request->product_id);

        if ($product->status !== 'active') {
            return redirect()->back()->with('error', 'Sản phẩm không khả dụng');
        }

        // Ưu tiên lấy giá giảm giá nếu có
        $currentPrice = $product->discount_price ?? $product->price;

        $cart = Cart::firstOrCreate(['user_id' => auth()->id()]);

        $cartItem = $cart->cartItems()->where('product_id', $request->product_id)->first();

        $newQuantity = $cartItem ? $cartItem->quantity + $request->quantity : $request->quantity;

        if ($product->stock < $newQuantity) {
            return redirect()->back()->with('error', "Số lượng sản phẩm không đủ. Còn lại: {$product->stock}");
        }

        if ($cartItem) {
            $cartItem->increment('quantity', $request->quantity);
            // Cập nhật lại giá mới nhất nếu muốn (tùy nghiệp vụ)
            $cartItem->update(['price' => $currentPrice]);
        } else {
            // 2. Truyền thêm 'price' vào đây để fix lỗi SQL
            $cart->cartItems()->create([
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
                'price' => $currentPrice, // Thêm dòng này
            ]);
        }

        return redirect()->back()->with('success', 'Đã thêm vào giỏ hàng!');
    }
}
