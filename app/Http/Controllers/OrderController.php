<?php
// app/Http/Controllers/OrderController.php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Str;      
use App\Models\OrderItem;
use App\Services\OrderPlacementService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    /**
     * Get user's orders (Customer)
     */
    public function myOrders(Request $request)
    {
        $query = Order::where('user_id', Auth::id())
            ->with(['orderItems.product', 'shippingAddress', 'promotion']);

        // Filter by status
        if ($request->status) {
            $query->where('order_status', $request->status);
        }

        $orders = $query->latest()->paginate(10);

        // THAY ĐỔI Ở ĐÂY: Trả về View của Inertia
        return \Inertia\Inertia::render('MyOrders', [
            'orders' => $orders,
            // 'filters' => $request->only(['status'])
        ]);
    }

    /**
     * Get order detail
     */
    public function show($id)
    {
        $order = Order::with(['orderItems.product', 'shippingAddress', 'promotion', 'payments'])
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        // THAY ĐỔI: Phải render ra một trang React cụ thể
        return Inertia::render('Order/OrderDetail', [
            'order' => $order
        ]);
    }

    /**
     * Tạo đơn hàng từ giỏ hàng hiện tại (dùng chung cho web + API).
     */
    public function store(Request $request, OrderPlacementService $placement)
    {
        $request->validate([
            'shipping_address_id' => 'nullable|exists:shipping_addresses,id',
            'payment_method' => 'nullable|in:cod,bank_transfer,momo,vnpay,zalopay',
            'note' => 'nullable|string|max:1000',
            'promotion_code' => 'nullable|string',
        ]);

        $isApi = $request->wantsJson() || $request->is('api/*');

        try {
            $order = $placement->placeFromCart(
                Auth::id(),
                $request->payment_method ?? 'cod',
                $request->shipping_address_id,
                $request->promotion_code,
                $request->note
            );
        } catch (ValidationException $e) {
            if ($isApi) {
                return response()->json([
                    'success' => false,
                    'message' => collect($e->errors())->flatten()->first(),
                    'errors' => $e->errors(),
                ], 422);
            }
            return back()->withErrors($e->errors());
        } catch (\Exception $e) {
            if ($isApi) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lỗi hệ thống: ' . $e->getMessage(),
                ], 500);
            }
            return redirect()->back()->withErrors(['message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
        }

        if ($isApi) {
            return response()->json([
                'success' => true,
                'message' => 'Đặt hàng thành công!',
                'data' => $order->load('orderItems'),
            ], 201);
        }

        return redirect()->route('dashboard')->with('success', 'Đặt hàng thành công!');
    }

    public function checkout()
    {
        // Bước thanh toán (địa chỉ, mã khuyến mãi, COD/MoMo) được gộp ngay trong trang giỏ hàng.
        return redirect()->route('cart.index');
    }

    /**
     * Cancel order (Customer)
     */
    public function cancel(Request $request, $id)
    {

        $request->validate(['reason' => 'required|string|max:500']);

        $order = Order::where('user_id', Auth::id())->findOrFail($id);
        $isApi = $request->wantsJson() || $request->is('api/*');

        if (!$order->canCancel()) {
            return $isApi
                ? response()->json(['success' => false, 'message' => 'Không thể hủy đơn hàng này'], 422)
                : back()->with('error', 'Không thể hủy đơn hàng này');
        }

        DB::transaction(function () use ($order, $request) {
            foreach ($order->orderItems as $item) {
                if ($item->product_id) {
                    \App\Models\Product::where('id', $item->product_id)->increment('stock', $item->quantity);
                }
            }

            $order->update([
                'order_status' => 'cancelled',
                'cancelled_reason' => $request->reason,
            ]);
        });

        if ($isApi) {
            return response()->json([
                'success' => true,
                'message' => 'Đã hủy đơn hàng thành công',
                'data' => $order->fresh(),
            ]);
        }

        // Trả về trang cũ với thông báo thành công
        return back()->with('success', 'Đã hủy đơn hàng thành công');
    }

    /**
     * Get all orders (Admin)
     */
    public function index(Request $request)
    {
        $query = Order::with(['user', 'orderItems.product', 'shippingAddress']);

        // Filter by status
        if ($request->status) {
            $query->where('order_status', $request->status);
        }

        // Search
        if ($request->search) {
            $query->where('order_code', 'like', "%{$request->search}%");
        }

        $orders = $query->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * Update order status (Admin)
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => ['required', Rule::in(Order::ORDER_STATUSES)],
        ]);

        $order = Order::findOrFail($id);
        $order->update(['order_status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái thành công',
            'data' => $order
        ]);
    }

    /**
     * Order statistics overview (Admin)
     */
    public function getStatistics()
    {
        $stats = [
            'total' => Order::count(),
            'pending' => Order::where('order_status', 'pending')->count(),
            'processing' => Order::where('order_status', 'processing')->count(),
            'shipping' => Order::where('order_status', 'shipping')->count(),
            'completed' => Order::where('order_status', 'completed')->count(),
            'cancelled' => Order::where('order_status', 'cancelled')->count(),
            'total_revenue' => Order::where('order_status', 'completed')
                ->where('payment_status', 'paid')
                ->sum('total_amount'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Export orders to CSV (Admin)
     */
    public function export(Request $request)
    {
        $query = Order::with('user:id,full_name,email');

        if ($request->status) {
            $query->where('order_status', $request->status);
        }

        $orders = $query->orderByDesc('created_at')->get();

        $csv = "Ma don,Khach hang,Email,Tong tien,Trang thai,Ngay tao\n";
        foreach ($orders as $o) {
            $csv .= implode(',', [
                $o->order_code,
                str_replace(',', ' ', $o->user->full_name ?? 'N/A'),
                $o->user->email ?? 'N/A',
                $o->total_amount,
                $o->order_status,
                $o->created_at,
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="orders.csv"',
        ]);
    }
}
