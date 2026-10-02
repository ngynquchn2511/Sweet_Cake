<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    // app/Http/Middleware/HandleInertiaRequests.php

    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user(),
            ],
            // THÊM DÒNG NÀY
            'cartCount' => function () use ($request) {
                if (!$request->user()) return 0;

                return \App\Models\CartItem::whereHas('cart', function ($query) use ($request) {
                    $query->where('user_id', $request->user()->id);
                })->sum('quantity'); // Hoặc ->count() nếu muốn đếm số dòng sản phẩm
            },

            // Số sản phẩm yêu thích - hiển thị badge trên Navbar
            'wishlistCount' => function () use ($request) {
                if (!$request->user()) return 0;

                return \App\Models\Wishlist::where('user_id', $request->user()->id)->count();
            },

            // Giữ lại các flash message nếu có
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ]);
    }
}
