<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'price',
        'discount_price',
        'description',
        'image',
        'images',
        'stock',
        'unit',
        'weight',
        'status',
        'view_count',
        'sold_count',
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
            'category_id' => 'integer',
            'price' => 'decimal:2',
            'discount_price' => 'decimal:2',
            'images' => 'array',
            'weight' => 'decimal:2',
            'stock' => 'integer',
            'view_count' => 'integer',
            'sold_count' => 'integer',
        ];
    }

    /**
     * Luật validate dùng chung cho Admin\ProductController (web) và ProductController (API)
     * để 2 kênh quản trị chấp nhận cùng một bộ dữ liệu (BUG-035).
     */
    public static function validationRules(?int $ignoreId = null): array
    {
        return [
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:200',
            'slug' => 'nullable|string|max:200|unique:products,slug' . ($ignoreId ? ',' . $ignoreId : ''),
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0|lt:price',
            // Tạo mới bắt buộc có ảnh, cập nhật thì giữ ảnh cũ nếu không gửi
            'image' => ($ignoreId ? 'nullable' : 'required') . '|image|mimes:jpeg,png,jpg,webp|max:2048',
            'stock' => 'required|integer|min:0',
            'unit' => 'required|string|max:50',
            'weight' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',
        ];
    }

    /**
     * Sinh slug duy nhất từ tên (thêm hậu tố -1, -2... nếu trùng), bỏ qua chính bản ghi đang sửa.
     */
    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = \Illuminate\Support\Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    /**
     * Giá bán thực tế: ưu tiên discount_price nếu có.
     */
    public function getFinalPrice()
    {
        return $this->discount_price ?? $this->price;
    }

    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }
}
