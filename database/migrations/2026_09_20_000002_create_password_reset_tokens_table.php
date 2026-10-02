<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bảng chuẩn của Laravel cho luồng quên mật khẩu qua web (Password::sendResetLink / broker),
        // bị thiếu hoàn toàn khiến toàn bộ chức năng "Quên mật khẩu" trên web luôn lỗi.
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
    }
};
