<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cột action là enum 5 giá trị (create, update, delete, login, logout) trong khi code ghi thêm
     * update_status, update_payment_status, cancel, reset_password, duplicate... → mọi thao tác đó
     * vỡ ràng buộc CSDL và trả lỗi 500 (đổi trạng thái đơn, huỷ đơn, đặt lại mật khẩu khách hàng).
     */
    public function up(): void
    {
        Schema::table('admin_logs', function (Blueprint $table) {
            $table->string('action', 50)->change();
        });
    }

    public function down(): void
    {
        Schema::table('admin_logs', function (Blueprint $table) {
            $table->enum('action', ['create', 'update', 'delete', 'login', 'logout'])->change();
        });
    }
};
