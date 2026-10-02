<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Cột position của bảng banners dùng enum cũ (home_slider, home_banner, sidebar)
     * trong khi BannerController::store()/update() và getPositions() lại validate theo
     * bộ giá trị khác (homepage_main, homepage_sub, sidebar, footer) - chỉ "sidebar" khớp
     * cả 2 bên, mọi vị trí khác đều vỡ ràng buộc CHECK ở tầng DB khi tạo/sửa banner.
     * Đồng bộ lại theo đúng bộ giá trị mà controller đang dùng.
     */
    public function up(): void
    {
        // Map dữ liệu cũ (nếu có) sang giá trị mới tương ứng trước khi đổi schema
        DB::table('banners')->where('position', 'home_slider')->update(['position' => 'homepage_main']);
        DB::table('banners')->where('position', 'home_banner')->update(['position' => 'homepage_sub']);

        Schema::table('banners', function (Blueprint $table) {
            $table->string('position_new', 20)->default('homepage_main')->after('position');
        });

        DB::table('banners')->update(['position_new' => DB::raw('position')]);

        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn('position');
        });

        Schema::table('banners', function (Blueprint $table) {
            $table->renameColumn('position_new', 'position');
        });
    }

    public function down(): void
    {
        // Không hoàn tác chi tiết enum cũ vì dữ liệu đã được chuẩn hoá theo bộ giá trị mới.
    }
};
