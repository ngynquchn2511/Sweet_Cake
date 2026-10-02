<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
    {
        // Lấy 8 sản phẩm mới nhất
        $products = Product::where('status', 'active')
            ->latest()
            ->take(8)
            ->get();

        // Banner đang hoạt động, gom theo vị trí hiển thị (homepage_main, homepage_sub, sidebar, footer)
        $banners = Banner::active()
            ->ordered()
            ->get()
            ->groupBy('position');

        return Inertia::render('Home', [
            'products' => $products,
            'banners' => $banners,
        ]);
    }
    public function about()
    {
        return \Inertia\Inertia::render('About'); // Chỉ cần render trang tĩnh
    }

    /**
     * Trang "Liên hệ" - form liên hệ + khung hỏi đáp gửi tới admin.
     */
    public function contact()
    {
        return Inertia::render('Contact');
    }
}
