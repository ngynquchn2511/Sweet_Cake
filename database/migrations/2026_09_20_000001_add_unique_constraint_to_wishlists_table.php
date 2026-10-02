<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Xoá các bản ghi trùng lặp (nếu có) trước khi thêm ràng buộc unique
        $duplicates = \DB::table('wishlists')
            ->select('user_id', 'product_id', \DB::raw('MIN(id) as keep_id'))
            ->groupBy('user_id', 'product_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $dup) {
            \DB::table('wishlists')
                ->where('user_id', $dup->user_id)
                ->where('product_id', $dup->product_id)
                ->where('id', '!=', $dup->keep_id)
                ->delete();
        }

        Schema::table('wishlists', function (Blueprint $table) {
            $table->unique(['user_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::table('wishlists', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'product_id']);
        });
    }
};
