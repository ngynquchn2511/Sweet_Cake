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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id');
            $table->string('name', 200);
            $table->string('slug', 220)->unique();
            $table->decimal('price', 10, 2);
            $table->decimal('discount_price', 10, 2)->nullable();
            $table->text('description')->nullable();
            $table->string('image');
            $table->json('images')->nullable();
            $table->string('stock')->default('0');
            $table->string('unit', 20)->default('chiếc');
            $table->decimal('weight', 8, 2)->nullable();
            $table->enum('status', ["active","inactive","out_of_stock"])->default('active');
            $table->string('view_count')->default('0');
            $table->string('sold_count')->default('0');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
