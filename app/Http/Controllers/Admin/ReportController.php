<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Category;
use App\Models\Review;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReportController extends Controller
{
    /**
     * Display reports dashboard
     */
    public function index(Request $request)
    {
        // Get date range from request or default to this month (to_date tính trọn cả ngày cuối)
        [$fromDate, $toDate] = $this->resolveDateRange($request);

        // 1. OVERVIEW STATS
        $stats = [
            'total_revenue' => $this->getTotalRevenue($fromDate, $toDate),
            'total_orders' => $this->getTotalOrders($fromDate, $toDate),
            'total_customers' => $this->getTotalCustomers($fromDate, $toDate),
            'total_products_sold' => $this->getTotalProductsSold($fromDate, $toDate),
            'avg_order_value' => $this->getAvgOrderValue($fromDate, $toDate),
            'completed_orders' => $this->getCompletedOrders($fromDate, $toDate),
        ];

        // 2. REVENUE CHART (Daily/Weekly/Monthly)
        $revenueChart = $this->getRevenueChart($fromDate, $toDate, $request->chart_type ?? 'daily');

        // 3. TOP SELLING PRODUCTS
        $topProducts = $this->getTopSellingProducts($fromDate, $toDate, 10);

        // 4. REVENUE BY CATEGORY
        $revenueByCategory = $this->getRevenueByCategory($fromDate, $toDate);

        // 5. ORDER STATUS BREAKDOWN
        $orderStatusStats = $this->getOrderStatusStats($fromDate, $toDate);

        // 6. TOP CUSTOMERS
        $topCustomers = $this->getTopCustomers($fromDate, $toDate, 10);

        // 7. TOP REVIEWED PRODUCTS
        $topReviewedProducts = $this->getTopReviewedProducts(10);

        return Inertia::render('Admin/Reports/Index', [
            'stats' => $stats,
            'revenueChart' => $revenueChart,
            'topProducts' => $topProducts,
            'revenueByCategory' => $revenueByCategory,
            'orderStatusStats' => $orderStatusStats,
            'topCustomers' => $topCustomers,
            'topReviewedProducts' => $topReviewedProducts,
            'filters' => [
                'from_date' => $fromDate->format('Y-m-d'),
                'to_date' => $toDate->format('Y-m-d'),
                'chart_type' => $request->chart_type ?? 'daily',
            ],
        ]);
    }

    /**
     * Get total revenue
     */
    private function getTotalRevenue($fromDate, $toDate)
    {
        return Order::whereBetween('created_at', [$fromDate, $toDate])
            ->where('order_status', 'completed')
            ->where('payment_status', 'paid')
            ->sum('total_amount');
    }

    /**
     * Get total orders
     */
    private function getTotalOrders($fromDate, $toDate)
    {
        return Order::whereBetween('created_at', [$fromDate, $toDate])->count();
    }

    /**
     * Get total customers
     */
    private function getTotalCustomers($fromDate, $toDate)
    {
        return User::where('role', 'customer')
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->count();
    }

    /**
     * Get total products sold
     */
    private function getTotalProductsSold($fromDate, $toDate)
    {
        return DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [$fromDate, $toDate])
            ->where('orders.order_status', 'completed')
            ->sum('order_items.quantity');
    }

    /**
     * Get average order value
     */
    private function getAvgOrderValue($fromDate, $toDate)
    {
        return Order::whereBetween('created_at', [$fromDate, $toDate])
            ->where('order_status', 'completed')
            ->avg('total_amount') ?? 0;
    }

    /**
     * Get completed orders
     */
    private function getCompletedOrders($fromDate, $toDate)
    {
        return Order::whereBetween('created_at', [$fromDate, $toDate])
            ->where('order_status', 'completed')
            ->count();
    }

    /**
     * Get revenue chart data
     */
    private function getRevenueChart($fromDate, $toDate, $type = 'daily')
    {
        $labels = [];
        $data = [];

        if ($type === 'daily') {
            // Daily for last 30 days
            for ($i = 29; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $labels[] = $date->format('d/m');
                
                $revenue = Order::whereDate('created_at', $date)
                    ->where('order_status', 'completed')
                    ->where('payment_status', 'paid')
                    ->sum('total_amount');
                
                $data[] = (float) $revenue;
            }
        } elseif ($type === 'weekly') {
            // Weekly for last 12 weeks
            for ($i = 11; $i >= 0; $i--) {
                $startOfWeek = Carbon::now()->subWeeks($i)->startOfWeek();
                $endOfWeek = Carbon::now()->subWeeks($i)->endOfWeek();
                $labels[] = $startOfWeek->format('d/m') . '-' . $endOfWeek->format('d/m');
                
                $revenue = Order::whereBetween('created_at', [$startOfWeek, $endOfWeek])
                    ->where('order_status', 'completed')
                    ->where('payment_status', 'paid')
                    ->sum('total_amount');
                
                $data[] = (float) $revenue;
            }
        } else {
            // Monthly for last 12 months
            for ($i = 11; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $labels[] = $date->format('m/Y');
                
                $revenue = Order::whereMonth('created_at', $date->month)
                    ->whereYear('created_at', $date->year)
                    ->where('order_status', 'completed')
                    ->where('payment_status', 'paid')
                    ->sum('total_amount');
                
                $data[] = (float) $revenue;
            }
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Get top selling products
     */
    private function getTopSellingProducts($fromDate, $toDate, $limit = 10)
    {
        return Product::select('products.id', 'products.name', 'products.image', 'products.price')
            ->selectRaw('COALESCE(SUM(order_items.quantity), 0) as total_sold')
            ->selectRaw('COALESCE(SUM(order_items.subtotal), 0) as total_revenue')
            ->leftJoin('order_items', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('orders', function($join) use ($fromDate, $toDate) {
                $join->on('order_items.order_id', '=', 'orders.id')
                     ->where('orders.order_status', '=', 'completed')
                     ->whereBetween('orders.created_at', [$fromDate, $toDate]);
            })
            ->groupBy('products.id', 'products.name', 'products.image', 'products.price')
            ->orderByDesc('total_sold')
            ->limit($limit)
            ->get();
    }

    /**
     * Get revenue by category
     */
    private function getRevenueByCategory($fromDate, $toDate)
    {
        return Category::select('categories.id', 'categories.name')
            ->selectRaw('COALESCE(SUM(order_items.subtotal), 0) as total_revenue')
            ->leftJoin('products', 'categories.id', '=', 'products.category_id')
            ->leftJoin('order_items', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('orders', function($join) use ($fromDate, $toDate) {
                $join->on('order_items.order_id', '=', 'orders.id')
                     ->where('orders.order_status', '=', 'completed')
                     ->whereBetween('orders.created_at', [$fromDate, $toDate]);
            })
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_revenue')
            ->get();
    }

    /**
     * Get order status statistics
     */
    private function getOrderStatusStats($fromDate, $toDate)
    {
        return Order::select('order_status')
            ->selectRaw('COUNT(*) as count')
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->groupBy('order_status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->order_status => $item->count];
            });
    }

    /**
     * Get top customers
     */
    private function getTopCustomers($fromDate, $toDate, $limit = 10)
    {
        return User::select('users.id', 'users.full_name', 'users.email', 'users.phone')
            ->selectRaw('COUNT(orders.id) as total_orders')
            ->selectRaw('COALESCE(SUM(orders.total_amount), 0) as total_spent')
            ->leftJoin('orders', function($join) use ($fromDate, $toDate) {
                $join->on('users.id', '=', 'orders.user_id')
                     ->where('orders.order_status', '=', 'completed')
                     ->whereBetween('orders.created_at', [$fromDate, $toDate]);
            })
            ->where('users.role', 'customer')
            ->groupBy('users.id', 'users.full_name', 'users.email', 'users.phone')
            ->orderByDesc('total_spent')
            ->limit($limit)
            ->get();
    }

    /**
     * Get top reviewed products (highest avg rating)
     */
    private function getTopReviewedProducts($limit = 10)
    {
        return Product::select('products.id', 'products.name', 'products.image', 'products.price')
            ->selectRaw('COUNT(reviews.id) as review_count')
            ->selectRaw('ROUND(AVG(reviews.rating), 1) as avg_rating')
            ->join('reviews', 'products.id', '=', 'reviews.product_id')
            ->where('reviews.status', 'approved')
            ->groupBy('products.id', 'products.name', 'products.image', 'products.price')
            ->having('review_count', '>=', 1)
            ->orderByDesc('avg_rating')
            ->orderByDesc('review_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Lấy khoảng thời gian from_date/to_date từ request, mặc định là tháng hiện tại.
     */
    private function resolveDateRange(Request $request): array
    {
        $fromDate = $request->from_date ? Carbon::parse($request->from_date)->startOfDay() : Carbon::now()->startOfMonth();
        $toDate = $request->to_date ? Carbon::parse($request->to_date)->endOfDay() : Carbon::now()->endOfMonth();

        return [$fromDate, $toDate];
    }

    /**
     * Báo cáo tổng quan rút gọn dùng cho widget dashboard
     */
    public function getDashboardReport(Request $request)
    {
        [$fromDate, $toDate] = $this->resolveDateRange($request);

        return response()->json([
            'success' => true,
            'data' => [
                'total_revenue' => $this->getTotalRevenue($fromDate, $toDate),
                'total_orders' => $this->getTotalOrders($fromDate, $toDate),
                'total_customers' => $this->getTotalCustomers($fromDate, $toDate),
                'total_products_sold' => $this->getTotalProductsSold($fromDate, $toDate),
                'avg_order_value' => $this->getAvgOrderValue($fromDate, $toDate),
                'completed_orders' => $this->getCompletedOrders($fromDate, $toDate),
            ],
        ]);
    }

    /**
     * Báo cáo doanh thu chi tiết theo biểu đồ
     */
    public function getRevenueReport(Request $request)
    {
        [$fromDate, $toDate] = $this->resolveDateRange($request);

        return response()->json([
            'success' => true,
            'data' => [
                'total_revenue' => $this->getTotalRevenue($fromDate, $toDate),
                'chart' => $this->getRevenueChart($fromDate, $toDate, $request->chart_type ?? 'daily'),
            ],
        ]);
    }

    /**
     * Báo cáo hiệu quả bán hàng theo sản phẩm
     */
    public function getProductReport(Request $request)
    {
        [$fromDate, $toDate] = $this->resolveDateRange($request);

        return response()->json([
            'success' => true,
            'data' => $this->getTopSellingProducts($fromDate, $toDate, $request->limit ?? 50),
        ]);
    }

    /**
     * Báo cáo doanh thu theo danh mục
     */
    public function getCategoryReport(Request $request)
    {
        [$fromDate, $toDate] = $this->resolveDateRange($request);

        return response()->json([
            'success' => true,
            'data' => $this->getRevenueByCategory($fromDate, $toDate),
        ]);
    }

    /**
     * Báo cáo khách hàng chi tiêu nhiều nhất
     */
    public function getCustomerReport(Request $request)
    {
        [$fromDate, $toDate] = $this->resolveDateRange($request);

        return response()->json([
            'success' => true,
            'data' => $this->getTopCustomers($fromDate, $toDate, $request->limit ?? 50),
        ]);
    }

    /**
     * Báo cáo tồn kho: sản phẩm sắp hết hàng / hết hàng
     */
    public function getInventoryReport(Request $request)
    {
        $products = Product::select('id', 'name', 'stock', 'sold_count', 'status')
            ->orderByRaw('CAST(stock AS UNSIGNED) asc')
            ->get()
            ->map(function ($p) {
                $stock = (int) $p->stock;
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'stock' => $stock,
                    'sold_count' => (int) $p->sold_count,
                    'status' => $p->status,
                    'inventory_status' => $stock <= 0 ? 'out_of_stock' : ($stock <= 10 ? 'low_stock' : 'in_stock'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'out_of_stock_count' => $products->where('inventory_status', 'out_of_stock')->count(),
                'low_stock_count' => $products->where('inventory_status', 'low_stock')->count(),
                'products' => $products,
            ],
        ]);
    }

    /**
     * Báo cáo trạng thái đơn hàng
     */
    public function getOrderStatusReport(Request $request)
    {
        [$fromDate, $toDate] = $this->resolveDateRange($request);

        return response()->json([
            'success' => true,
            'data' => $this->getOrderStatusStats($fromDate, $toDate),
        ]);
    }

    /**
     * Báo cáo theo phương thức thanh toán
     */
    public function getPaymentMethodReport(Request $request)
    {
        [$fromDate, $toDate] = $this->resolveDateRange($request);

        $data = Order::select('payment_method')
            ->selectRaw('COUNT(*) as total_orders')
            ->selectRaw('SUM(total_amount) as total_amount')
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->groupBy('payment_method')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Xuất báo cáo tổng quan ra CSV
     */
    public function exportReport(Request $request)
    {
        [$fromDate, $toDate] = $this->resolveDateRange($request);

        $topProducts = $this->getTopSellingProducts($fromDate, $toDate, 50);

        $csv = "San pham,So luong ban,Doanh thu\n";
        foreach ($topProducts as $p) {
            $csv .= implode(',', [
                str_replace(',', ' ', $p->name),
                $p->total_sold,
                $p->total_revenue,
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="bao-cao-' . $fromDate->format('Y-m-d') . '.csv"',
        ]);
    }
}