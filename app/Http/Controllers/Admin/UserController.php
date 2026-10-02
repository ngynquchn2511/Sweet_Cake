<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use App\Models\AdminLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Display user list
     */
    public function index(Request $request)
    {
        $query = User::where('role', 'customer');

        // Search
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('full_name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%");
            });
        }

        // Filter by status
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Sort (whitelist cột để tránh lỗi SQL từ input tuỳ ý)
        $sortBy = in_array($request->sort_by, ['created_at', 'full_name', 'email', 'status'], true) ? $request->sort_by : 'created_at';
        $sortOrder = $request->sort_order === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // Paginate with order count and total spent
        $users = $query->withCount(['orders as total_orders' => function($q) {
                $q->where('order_status', 'completed');
            }])
            ->withSum(['orders as total_spent' => function($q) {
                $q->where('order_status', 'completed');
            }], 'total_amount')
            ->paginate($request->per_page ?? 15);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'status', 'sort_by', 'sort_order']),
        ]);
    }

    /**
     * Show user detail
     */
    public function show($id)
    {
        $user = User::findOrFail($id);

        // Get user orders with pagination
        $orders = Order::where('user_id', $id)
            ->with(['orderItems.product'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Get statistics in ONE query using raw SQL
        $statsRaw = DB::selectOne("
            SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN order_status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
                SUM(CASE WHEN order_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
                SUM(CASE WHEN order_status = 'completed' THEN total_amount ELSE 0 END) as total_spent,
                AVG(CASE WHEN order_status = 'completed' THEN total_amount ELSE NULL END) as avg_order_value,
                MAX(created_at) as last_order_date
            FROM orders 
            WHERE user_id = ?
        ", [$id]);

        $stats = [
            'total_orders'      => (int) $statsRaw->total_orders,
            'completed_orders'  => (int) $statsRaw->completed_orders,
            'cancelled_orders'  => (int) $statsRaw->cancelled_orders,
            'total_spent'       => (float) ($statsRaw->total_spent ?? 0),
            'avg_order_value'   => (float) ($statsRaw->avg_order_value ?? 0),
            'last_order_date'   => $statsRaw->last_order_date,
        ];

        return Inertia::render('Admin/Users/Detail', [
            'user' => $user,
            'orders' => $orders,
            'stats' => $stats,
        ]);
    }

    /**
     * Update user status
     */
    public function updateStatus(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:active,banned',
        ]);

        $oldStatus = $user->status;

        $user->update([
            'status' => $validated['status'],
        ]);

        AdminLog::record('update_status', 'users', $user->id, ['status' => $oldStatus], ['status' => $validated['status']]);

        return back()->with('success', 
            $validated['status'] === 'banned' 
                ? 'Đã chặn tài khoản khách hàng!' 
                : 'Đã mở khóa tài khoản khách hàng!'
        );
    }

    /**
     * Delete user
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Check if user has orders
        if ($user->orders()->count() > 0) {
            return back()->with('error', 'Không thể xóa khách hàng đã có đơn hàng!');
        }

        AdminLog::record('delete', 'users', $user->id, $user->only(['full_name', 'email', 'status']));

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Xóa khách hàng thành công!');
    }

    /**
     * Get user statistics
     */
    public function getStatistics()
    {
        $stats = [
            'total_customers' => User::where('role', 'customer')->count(),
            'active_customers' => User::where('role', 'customer')
                ->where('status', 'active')
                ->count(),
            'banned_customers' => User::where('role', 'customer')
                ->where('status', 'banned')
                ->count(),
            'customers_with_orders' => User::where('role', 'customer')
                ->has('orders')
                ->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Create a new customer account (Admin)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|max:50|unique:users,username',
            'full_name' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:users,email',
            'phone' => 'required|string|max:15|unique:users,phone',
            'password' => 'required|string|min:6',
            'status' => 'nullable|in:active,banned',
        ]);

        $user = User::create([
            'username' => $validated['username'],
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'role' => 'customer',
            'status' => $validated['status'] ?? 'active',
        ]);

        AdminLog::create([
            'user_id' => auth()->id(),
            'action' => 'create',
            'table_name' => 'users',
            'record_id' => $user->id,
            'new_value' => $user->only(['full_name', 'email', 'phone', 'status']),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tạo khách hàng thành công',
            'data' => $user,
        ], 201);
    }

    /**
     * Update a customer account (Admin)
     */
    public function update(Request $request, $id)
    {
        $user = User::where('role', 'customer')->findOrFail($id);

        $validated = $request->validate([
            'full_name' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:users,email,' . $id,
            'phone' => 'required|string|max:15|unique:users,phone,' . $id,
        ]);

        $oldValue = $user->only(['full_name', 'email', 'phone']);

        $user->update($validated);

        AdminLog::create([
            'user_id' => auth()->id(),
            'action' => 'update',
            'table_name' => 'users',
            'record_id' => $user->id,
            'old_value' => $oldValue,
            'new_value' => $validated,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật khách hàng thành công',
            'data' => $user,
        ]);
    }

    /**
     * Export customer list as CSV
     */
    public function export(Request $request)
    {
        $customers = User::where('role', 'customer')->get([
            'id', 'username', 'full_name', 'email', 'phone', 'status', 'created_at',
        ]);

        $csv = "ID,Username,Ho ten,Email,SDT,Trang thai,Ngay tao\n";
        foreach ($customers as $c) {
            $csv .= implode(',', [
                $c->id,
                $c->username,
                str_replace(',', ' ', $c->full_name),
                $c->email,
                $c->phone,
                $c->status,
                $c->created_at,
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="customers.csv"',
        ]);
    }

    /**
     * Get admin actions performed on this customer record
     */
    public function getActivityLog($id)
    {
        User::where('role', 'customer')->findOrFail($id);

        $logs = AdminLog::where('table_name', 'users')
            ->where('record_id', $id)
            ->with('user:id,full_name')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    /**
     * Get full order history of a customer
     */
    public function getOrderHistory($id)
    {
        User::where('role', 'customer')->findOrFail($id);

        $orders = Order::where('user_id', $id)
            ->with(['orderItems.product', 'shippingAddress'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * Reset a customer's password (Admin support tool)
     */
    public function resetPassword(Request $request, $id)
    {
        $user = User::where('role', 'customer')->findOrFail($id);

        $newPassword = Str::random(10);
        $user->update(['password' => Hash::make($newPassword)]);

        AdminLog::create([
            'user_id' => auth()->id(),
            'action' => 'reset_password',
            'table_name' => 'users',
            'record_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Gửi mật khẩu tạm thời tới email của khách để họ đăng nhập và tự đổi lại
        \Illuminate\Support\Facades\Mail::raw(
            "Xin chào {$user->full_name},\n\nMật khẩu tạm thời của bạn tại Sweet Bakery là: {$newPassword}\n"
            . "Vui lòng đăng nhập và đổi mật khẩu ngay tại trang \"Đổi mật khẩu\".",
            fn ($mail) => $mail->to($user->email)->subject('Sweet Bakery - Mật khẩu tạm thời')
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã đặt lại mật khẩu và gửi mật khẩu tạm thời tới email khách hàng',
            'new_password' => $newPassword,
        ]);
    }
}