<?php
// app/Http/Controllers/Admin/DashboardController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Display dashboard
     */
    public function index()
    {
        // 1. THỐNG KÊ TỔNG QUAN
        $stats = [
            // Doanh thu
            'revenue_today' => $this->getRevenueToday(),
            'revenue_week' => $this->getRevenueWeek(),
            'revenue_month' => $this->getRevenueMonth(),
            'revenue_year' => $this->getRevenueYear(),
            
            // Đơn hàng
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('order_status', 'pending')->count(),
            'processing_orders' => Order::where('order_status', 'processing')->count(),
            'completed_orders' => Order::where('order_status', 'completed')->count(),
            'cancelled_orders' => Order::where('order_status', 'cancelled')->count(),
            
            // Khách hàng
            'total_customers' => User::where('role', 'customer')->count(),
            'new_customers_today' => User::where('role', 'customer')
                ->whereDate('created_at', today())
                ->count(),
            'new_customers_month' => User::where('role', 'customer')
                ->whereMonth('created_at', now()->month)
                ->count(),
            'active_customers' => User::where('role', 'customer')
                ->where('status', 'active')
                ->count(),
            
            // Sản phẩm
            'total_products' => Product::count(),
            'active_products' => Product::where('status', 'active')->count(),
            // Hết hàng thật sự (tồn kho = 0) - tách bạch với "sắp hết hàng" (1..10)
            'out_of_stock' => Product::where('stock', '<=', 0)->count(),
            'low_stock' => Product::where('stock', '>', 0)
                ->where('stock', '<=', 10)
                ->count(),
            
            // Top selling products
            'top_selling_products' => $this->getTopSellingProducts(),
            
            // Low stock products
            'low_stock_products' => Product::where('stock', '>', 0)
                ->where('stock', '<=', 10)
                ->orderBy('stock', 'asc')
                ->limit(10)
                ->get(),

            // Biểu đồ/bảng phụ trên Dashboard
            'revenue_chart_7_days' => $this->getRevenueChart7Days(),
            'revenue_by_month' => $this->getRevenueByMonth(),
            'top_customers' => $this->getTopCustomers(),
        ];

        // Return Inertia view
        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
        ]);
    }

    /**
     * Get revenue today
     */
    private function getRevenueToday()
    {
        return Order::whereDate('created_at', today())
            ->where('order_status', 'completed')
            ->where('payment_status', 'paid')
            ->sum('total_amount');
    }

    /**
     * Get revenue this week
     */
    private function getRevenueWeek()
    {
        return Order::whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])
            ->where('order_status', 'completed')
            ->where('payment_status', 'paid')
            ->sum('total_amount');
    }

    /**
     * Get revenue this month
     */
    private function getRevenueMonth()
    {
        return Order::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->where('order_status', 'completed')
            ->where('payment_status', 'paid')
            ->sum('total_amount');
    }

    /**
     * Get revenue this year
     */
    private function getRevenueYear()
    {
        return Order::whereYear('created_at', now()->year)
            ->where('order_status', 'completed')
            ->where('payment_status', 'paid')
            ->sum('total_amount');
    }

    /**
     * Get revenue chart for last 7 days
     */
    private function getRevenueChart7Days()
    {
        $labels = [];
        $data = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $labels[] = $date->format('d/m');
            
            $revenue = Order::whereDate('created_at', $date)
                ->where('order_status', 'completed')
                ->where('payment_status', 'paid')
                ->sum('total_amount');
            
            $data[] = (float) $revenue;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Get revenue by month (12 months)
     */
    private function getRevenueByMonth()
    {
        $labels = [];
        $data = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $labels[] = $date->format('m/Y');
            
            $revenue = Order::whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->where('order_status', 'completed')
                ->where('payment_status', 'paid')
                ->sum('total_amount');
            
            $data[] = (float) $revenue;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Get top selling products
     */
    private function getTopSellingProducts()
    {
        return Product::select(
                'products.id',
                'products.name',
                'products.image',
                'products.price'
            )
            ->selectRaw('COALESCE(SUM(order_items.quantity), 0) as total_sold')
            ->selectRaw('COALESCE(SUM(order_items.subtotal), 0) as total_revenue')
            ->leftJoin('order_items', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('orders', function($join) {
                $join->on('order_items.order_id', '=', 'orders.id')
                     ->where('orders.order_status', '=', 'completed');
            })
            ->groupBy('products.id', 'products.name', 'products.image', 'products.price')
            ->orderByDesc('total_sold')
            ->limit(10)
            ->get();
    }

    /**
     * Get top customers
     */
    private function getTopCustomers()
    {
        return User::select(
                'users.id',
                'users.full_name',
                'users.email',
                'users.phone'
            )
            ->selectRaw('COUNT(orders.id) as total_orders')
            ->selectRaw('COALESCE(SUM(orders.total_amount), 0) as total_spent')
            ->leftJoin('orders', function($join) {
                $join->on('users.id', '=', 'orders.user_id')
                     ->where('orders.order_status', '=', 'completed');
            })
            ->where('users.role', 'customer')
            ->groupBy('users.id', 'users.full_name', 'users.email', 'users.phone')
            ->orderByDesc('total_spent')
            ->limit(10)
            ->get();
    }

    /**
     * Get statistics by date range
     */
    public function getStatsByDateRange(Request $request)
    {
        $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
        ]);

        $fromDate = Carbon::parse($request->from_date)->startOfDay();
        $toDate = Carbon::parse($request->to_date)->endOfDay();

        $stats = [
            'total_revenue' => Order::whereBetween('created_at', [$fromDate, $toDate])
                ->where('order_status', 'completed')
                ->where('payment_status', 'paid')
                ->sum('total_amount'),
            
            'total_orders' => Order::whereBetween('created_at', [$fromDate, $toDate])->count(),
            
            'completed_orders' => Order::whereBetween('created_at', [$fromDate, $toDate])
                ->where('order_status', 'completed')
                ->count(),
            
            'new_customers' => User::whereBetween('created_at', [$fromDate, $toDate])
                ->where('role', 'customer')
                ->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get order statistics by status
     */
    public function getOrderStatsByStatus()
    {
        $stats = Order::select('order_status')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('SUM(total_amount) as total_amount')
            ->groupBy('order_status')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get sales by category
     */
    public function getSalesByCategory()
    {
        $categories = DB::table('categories')
            ->select('categories.id', 'categories.name')
            ->selectRaw('COALESCE(SUM(order_items.quantity), 0) as total_quantity')
            ->selectRaw('COALESCE(SUM(order_items.subtotal), 0) as total_revenue')
            ->leftJoin('products', 'categories.id', '=', 'products.category_id')
            ->leftJoin('order_items', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('orders', function($join) {
                $join->on('order_items.order_id', '=', 'orders.id')
                     ->where('orders.order_status', '=', 'completed');
            })
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_revenue')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }
}