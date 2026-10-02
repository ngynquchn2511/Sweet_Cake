<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Các cột số lượng/tồn kho/lượt xem/điểm đánh giá bị khai báo kiểu chuỗi (string) nên:
     * - so sánh/lọc số (stock <= 10, stock > 0) cho kết quả sai trên SQLite,
     * - sắp xếp theo tồn kho ra thứ tự từ điển ("10" đứng trước "9") trên mọi CSDL.
     * Chuyển về kiểu số nguyên đúng bản chất dữ liệu.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('stock')->default(0)->change();
            $table->unsignedInteger('view_count')->default(0)->change();
            $table->unsignedInteger('sold_count')->default(0)->change();
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->default(1)->change();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->change();
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->unsignedTinyInteger('rating')->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('stock')->default('0')->change();
            $table->string('view_count')->default('0')->change();
            $table->string('sold_count')->default('0')->change();
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->string('quantity')->default('1')->change();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('quantity')->change();
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->string('rating')->change();
        });
    }
};
