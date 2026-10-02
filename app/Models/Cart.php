<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
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
            'user_id' => 'integer',
        ];
    }
    

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function getSubtotal()
    {
        // Sử dụng $item->product (dấu ->) thay vì dấu chấm
        return $this->cartItems->sum(function ($item) {
            $price = $item->product->discount_price ?? $item->product->price;
            return $price * $item->quantity;
        });
    }

    /**
     * Tính tổng số lượng món trong giỏ
     */
    public function getTotalItems()
    {
        return $this->cartItems->sum('quantity');
    }

    /**
     * Xoá toàn bộ sản phẩm trong giỏ hàng.
     */
    public function clear(): void
    {
        $this->cartItems()->delete();
    }

    /**
     * Đồng bộ giỏ hàng với dữ liệu sản phẩm hiện tại: cập nhật giá mới, giảm số lượng
     * về mức tồn kho còn lại, loại bỏ sản phẩm đã ngừng bán/hết hàng.
     *
     * @return array{removed: array, updated: array}
     */
    public function syncWithProducts(): array
    {
        $removed = [];
        $updated = [];

        foreach ($this->cartItems()->with('product')->get() as $item) {
            $product = $item->product;

            if (!$product || $product->status !== 'active' || (int) $product->stock <= 0) {
                $removed[] = $item->toArray();
                $item->delete();
                continue;
            }

            $changes = [];
            if ((int) $item->quantity > (int) $product->stock) {
                $changes['quantity'] = (int) $product->stock;
            }
            if ((float) $item->price != (float) $product->getFinalPrice()) {
                $changes['price'] = $product->getFinalPrice();
            }

            if ($changes) {
                $item->update($changes);
                $updated[] = $item->toArray();
            }
        }

        $this->unsetRelation('cartItems');

        return ['removed' => $removed, 'updated' => $updated];
    }
}
