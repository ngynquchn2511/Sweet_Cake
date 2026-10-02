<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Khi khách xoá tài khoản, đơn hàng (và địa chỉ giao hàng mà đơn đó tham chiếu) vẫn phải được giữ lại
     * cho mục đích đối soát doanh thu/kế toán - nên user_id của 2 bảng này phải cho phép NULL
     * để được "tách" khỏi tài khoản đã xoá thay vì trở thành bản ghi mồ côi trỏ tới user không tồn tại.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });

        Schema::table('shipping_addresses', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });

        Schema::table('shipping_addresses', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
};
