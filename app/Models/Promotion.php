<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promotion extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code',
        'description',
        'discount_type',
        'discount_value',
        'min_order_value',
        'max_discount',
        'usage_limit',
        'used_count',
        'start_date',
        'end_date',
        'status',
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
            'discount_value' => 'decimal:2',
            'min_order_value' => 'decimal:2',
            'max_discount' => 'decimal:2',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Kiểm tra mã khuyến mãi và tính số tiền được giảm cho 1 đơn hàng.
     * Dùng chung cho cả công cụ kiểm tra của admin lẫn luồng checkout thật của khách hàng.
     *
     * @return array{valid: bool, message: string, promotion: ?self, discount_amount: float}
     */
    public static function validateAndCalculate(string $code, float $orderAmount): array
    {
        $promotion = static::where('code', strtoupper($code))->first();

        if (!$promotion) {
            return ['valid' => false, 'message' => 'Mã khuyến mãi không tồn tại!', 'promotion' => null, 'discount_amount' => 0];
        }

        if ($promotion->status !== 'active') {
            return ['valid' => false, 'message' => 'Mã khuyến mãi không còn hiệu lực!', 'promotion' => $promotion, 'discount_amount' => 0];
        }

        $now = now();
        if ($now->lt($promotion->start_date)) {
            return ['valid' => false, 'message' => 'Mã khuyến mãi chưa bắt đầu áp dụng!', 'promotion' => $promotion, 'discount_amount' => 0];
        }
        if ($now->gt($promotion->end_date)) {
            return ['valid' => false, 'message' => 'Mã khuyến mãi đã hết hạn!', 'promotion' => $promotion, 'discount_amount' => 0];
        }

        if ($promotion->usage_limit && $promotion->used_count >= $promotion->usage_limit) {
            return ['valid' => false, 'message' => 'Mã khuyến mãi đã hết lượt sử dụng!', 'promotion' => $promotion, 'discount_amount' => 0];
        }

        if ($promotion->min_order_value && $orderAmount < $promotion->min_order_value) {
            return [
                'valid' => false,
                'message' => "Đơn hàng tối thiểu {$promotion->min_order_value}đ để sử dụng mã này!",
                'promotion' => $promotion,
                'discount_amount' => 0,
            ];
        }

        if ($promotion->discount_type === 'percent') {
            $discount = ($orderAmount * $promotion->discount_value) / 100;
            if ($promotion->max_discount && $discount > $promotion->max_discount) {
                $discount = $promotion->max_discount;
            }
        } else {
            $discount = (float) $promotion->discount_value;
        }

        $discount = min($discount, $orderAmount);

        return ['valid' => true, 'message' => 'Mã khuyến mãi hợp lệ!', 'promotion' => $promotion, 'discount_amount' => $discount];
    }
}
