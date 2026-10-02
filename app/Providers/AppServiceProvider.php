<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Giới hạn tần suất gửi form Liên hệ / Hỏi-đáp theo IP (mỗi form 1 bộ đếm riêng) để chống spam
        \Illuminate\Support\Facades\RateLimiter::for('contact-form', fn ($request) =>
            \Illuminate\Cache\RateLimiting\Limit::perMinute(10)->by('contact|' . $request->ip()));
        \Illuminate\Support\Facades\RateLimiter::for('message-form', fn ($request) =>
            \Illuminate\Cache\RateLimiting\Limit::perMinute(10)->by('message|' . $request->ip()));

        // Badge đỏ đơn hàng mới
        \Inertia\Inertia::share('newOrderCount', function () {
    if (auth()->check() && auth()->user()->role === 'admin') {
        return \App\Models\Order::whereIn('order_status', ['pending', 'processing'])->count();
    }
    return 0;
});



        // Badge đỏ tin nhắn mới
        \Inertia\Inertia::share('newMessageCount', function () {
            if (auth()->check() && auth()->user()->role === 'admin') {
                return \App\Models\Message::where('status', 'unread')->count();
            }
            return 0;
        });

        Vite::prefetch(concurrency: 3);
    }
}