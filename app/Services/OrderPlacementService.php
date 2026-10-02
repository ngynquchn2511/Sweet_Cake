<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\ShippingAddress;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Tạo đơn hàng từ giỏ hàng - dùng chung cho COD (web), OrderController::store (web + API)
 * và thanh toán MoMo, để mọi luồng đặt hàng có cùng một bộ quy tắc nghiệp vụ.
 */
class OrderPlacementService
{
    public const SHIPPING_FEE = 30000;

    /**
     * @throws ValidationException khi giỏ trống, thiếu địa chỉ, mã khuyến mãi sai
     *                             hoặc sản phẩm đã ngừng bán/không đủ tồn kho tại thời điểm đặt.
     */
    public function placeFromCart(
        int $userId,
        string $paymentMethod = 'cod',
        ?int $shippingAddressId = null,
        ?string $promotionCode = null,
        ?string $note = null
    ): Order {
        $cart = Cart::where('user_id', $userId)->with('cartItems.product')->first();

        if (!$cart || $cart->cartItems->isEmpty()) {
            throw ValidationException::withMessages(['cart' => 'Giỏ hàng của bạn đang trống.']);
        }

        $shippingAddress = $shippingAddressId
            ? ShippingAddress::where('user_id', $userId)->find($shippingAddressId)
            : ShippingAddress::where('user_id', $userId)->orderByDesc('is_default')->orderBy('id')->first();

        if (!$shippingAddress) {
            throw ValidationException::withMessages([
                'shipping_address_id' => 'Vui lòng thêm địa chỉ giao hàng trước khi đặt hàng.',
            ]);
        }

        return DB::transaction(function () use ($cart, $userId, $paymentMethod, $shippingAddress, $promotionCode, $note) {
            // Kiểm tra lại trạng thái + tồn kho ngay tại bước chốt đơn (khoá dòng để tránh bán vượt kho
            // khi admin hạ tồn kho hoặc ngừng bán sau khi khách đã thêm vào giỏ).
            $products = Product::whereIn('id', $cart->cartItems->pluck('product_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $subtotal = 0;
            foreach ($cart->cartItems as $item) {
                $product = $products->get($item->product_id);

                if (!$product || $product->status !== 'active') {
                    throw ValidationException::withMessages([
                        'cart' => 'Sản phẩm "' . ($item->product->name ?? '#' . $item->product_id) . '" đã ngừng bán, vui lòng xoá khỏi giỏ hàng.',
                    ]);
                }

                if ((int) $product->stock < (int) $item->quantity) {
                    throw ValidationException::withMessages([
                        'cart' => "Sản phẩm \"{$product->name}\" chỉ còn {$product->stock} chiếc, vui lòng cập nhật lại số lượng.",
                    ]);
                }

                $subtotal += $item->price * $item->quantity;
            }

            $promotion = null;
            $discountAmount = 0;
            if ($promotionCode) {
                $result = Promotion::validateAndCalculate($promotionCode, (float) $subtotal);
                if (!$result['valid']) {
                    throw ValidationException::withMessages(['promotion_code' => $result['message']]);
                }
                $promotion = $result['promotion'];
                $discountAmount = $result['discount_amount'];
            }

            $order = Order::create([
                'user_id' => $userId,
                'shipping_address_id' => $shippingAddress->id,
                'promotion_id' => $promotion?->id,
                'order_code' => 'ORD-' . strtoupper(Str::random(10)),
                'subtotal' => $subtotal,
                'shipping_fee' => self::SHIPPING_FEE,
                'discount_amount' => $discountAmount,
                'total_amount' => $subtotal + self::SHIPPING_FEE - $discountAmount,
                'payment_method' => $paymentMethod,
                'payment_status' => 'unpaid',
                'order_status' => 'pending',
                'note' => $note,
            ]);

            $promotion?->increment('used_count');

            foreach ($cart->cartItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'subtotal' => $item->price * $item->quantity,
                ]);

                $products->get($item->product_id)->decrement('stock', (int) $item->quantity);
            }

            $cart->cartItems()->delete();

            return $order;
        });
    }
}
