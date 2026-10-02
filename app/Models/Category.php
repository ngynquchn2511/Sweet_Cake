<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'image',
        'status',
        'display_order',
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
        ];
    }

    /**
     * Luật validate dùng chung cho Admin\CategoryController (web) và CategoryController (API) (BUG-035).
     */
    public static function validationRules(?int $ignoreId = null): array
    {
        $ignore = $ignoreId ? ',' . $ignoreId : '';

        return [
            'name' => 'required|string|max:100|unique:categories,name' . $ignore,
            'slug' => 'nullable|string|max:100|unique:categories,slug' . $ignore,
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'status' => 'required|in:active,inactive',
            'display_order' => 'nullable|integer|min:0',
        ];
    }

    /**
     * Sinh slug duy nhất từ tên, bỏ qua chính bản ghi đang sửa.
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

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
