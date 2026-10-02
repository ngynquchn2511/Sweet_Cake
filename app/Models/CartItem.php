<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'cart_id',
        'product_id',
        'quantity',
        'price',
        'status',
        'abandoned_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'cart_id' => 'integer',
            'product_id' => 'integer',
            'quantity' => 'integer',
            'price' => 'decimal:2',
            'abandoned_at' => 'datetime',
        ];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Đánh dấu các sản phẩm nằm trong giỏ quá $days ngày chưa đặt hàng là "abandoned".
     * Được gọi tự động mỗi ngày qua scheduler (routes/console.php) và từ nút bấm ở trang Cart Analytics.
     */
    public static function markAbandoned(int $days = 30): int
    {
        return static::where('status', 'active')
            ->where('created_at', '<', now()->subDays($days))
            ->update([
                'status' => 'abandoned',
                'abandoned_at' => now(),
            ]);
    }
}
