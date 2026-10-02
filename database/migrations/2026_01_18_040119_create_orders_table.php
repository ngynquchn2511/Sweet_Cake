<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('shipping_address_id')->nullable();
            $table->foreignId('promotion_id')->nullable();
            $table->string('order_code', 20)->unique();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('shipping_fee', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->enum('order_status', ["pending","confirmed","processing","shipping","completed","cancelled","refunded"])->default('pending');
            $table->enum('payment_method', ["cod","bank_transfer","momo","vnpay","zalopay"])->default('cod');
            $table->enum('payment_status', ["unpaid","paid","refunded"])->default('unpaid');
            $table->text('note')->nullable();
            $table->text('cancelled_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
