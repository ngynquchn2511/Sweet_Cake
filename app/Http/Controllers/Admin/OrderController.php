<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\AdminLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class OrderController extends Controller
{
    /**
     * Display order list
     */
    public function index(Request $request)
    {
        $query = Order::with(['user', 'orderItems.product']);

        // Search by order code or customer name
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('order_code', 'like', "%{$request->search}%")
                  ->orWhereHas('user', function($q2) use ($request) {
                      $q2->where('full_name', 'like', "%{$request->search}%")
                         ->orWhere('email', 'like', "%{$request->search}%");
                  });
            });
        }

        // Filter by order status
        if ($request->order_status) {
            $query->where('order_status', $request->order_status);
        }

        // Filter by payment status
        if ($request->payment_status) {
            $query->where('payment_status', $request->payment_status);
        }

        // Filter by date range
        if ($request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Sort (whitelist cột để tránh lỗi SQL từ input tuỳ ý)
        $sortBy = in_array($request->sort_by, ['created_at', 'order_code', 'total_amount', 'order_status'], true) ? $request->sort_by : 'created_at';
        $sortOrder = $request->sort_order === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // Paginate
        $orders = $query->paginate($request->per_page ?? 15);

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
            'filters' => $request->only([
                'search', 'order_status', 'payment_status', 
                'from_date', 'to_date', 'sort_by', 'sort_order'
            ]),
        ]);
    }

    /**
     * Show order detail
     */
    public function show($id)
    {
        $order = Order::with([
            'user',
            'orderItems.product',
            'payment',
        ])->findOrFail($id);

        return Inertia::render('Admin/Orders/Detail', [
            'order' => $order,
        ]);
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $validated = $request->validate([
            'order_status' => ['required', Rule::in(Order::ORDER_STATUSES)],
        ]);

        $oldStatus = $order->order_status;

        $order->update([
            'order_status' => $validated['order_status'],
        ]);

        // Lưu ý: KHÔNG tự động đánh dấu "đã thanh toán" khi đơn chuyển "completed" nữa.
        // Với đơn COD/chuyển khoản, việc xác nhận đã thu tiền phải là thao tác riêng
        // qua updatePaymentStatus() để tránh sai lệch báo cáo doanh thu.

        AdminLog::create([
            'user_id' => auth()->id(),
            'action' => 'update_status',
            'table_name' => 'orders',
            'record_id' => $order->id,
            'old_value' => ['order_status' => $oldStatus],
            'new_value' => ['order_status' => $validated['order_status']],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'Cập nhật trạng thái đơn hàng thành công!');
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $validated = $request->validate([
            'payment_status' => ['required', Rule::in(Order::PAYMENT_STATUSES)],
        ]);

        $oldStatus = $order->payment_status;

        $order->update([
            'payment_status' => $validated['payment_status'],
        ]);

        AdminLog::create([
            'user_id' => auth()->id(),
            'action' => 'update_payment_status',
            'table_name' => 'orders',
            'record_id' => $order->id,
            'old_value' => ['payment_status' => $oldStatus],
            'new_value' => ['payment_status' => $validated['payment_status']],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'Cập nhật trạng thái thanh toán thành công!');
    }

    /**
     * Cancel order
     */
    public function destroy($id)
    {
        $order = Order::findOrFail($id);

        // Only can cancel pending orders
        if ($order->order_status !== 'pending') {
            return back()->with('error', 'Chỉ có thể hủy đơn hàng đang chờ xử lý!');
        }

        $order->update([
            'order_status' => 'cancelled',
        ]);

        AdminLog::create([
            'user_id' => auth()->id(),
            'action' => 'cancel',
            'table_name' => 'orders',
            'record_id' => $order->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return redirect()
            ->route('admin.orders.index')
            ->with('success', 'Hủy đơn hàng thành công!');
    }

    /**
     * Get order statistics
     */
    public function statistics()
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

        return response()->json($stats);
    }
    public function markRead()
    {
        session(['orders_last_seen_at' => now()->toDateTimeString()]);
        return back();
    }
}