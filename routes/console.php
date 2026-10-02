<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\CartItem;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Tự động đánh dấu giỏ hàng bị bỏ quên (> 30 ngày) mỗi ngày, không phụ thuộc admin bấm tay.
// Chạy scheduler bằng: php artisan schedule:work (dev) hoặc cron "* * * * * php artisan schedule:run".
Artisan::command('cart:mark-abandoned {--days=30}', function () {
    $count = CartItem::markAbandoned((int) $this->option('days'));
    $this->info("Đã đánh dấu {$count} sản phẩm trong giỏ là abandoned.");
})->purpose('Đánh dấu các sản phẩm nằm trong giỏ quá lâu là abandoned');

Schedule::command('cart:mark-abandoned')->daily();
