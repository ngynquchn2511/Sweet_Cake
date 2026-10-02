<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingAddress extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'receiver_name',
        'phone',
        'province',
        'district',
        'ward',
        'address',
        'note',
        'is_default',
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
            'is_default' => 'boolean',
        ];
    }

    /**
     * Luật validate dùng chung cho cả web (ShippingAddressController) và API (ProfileController),
     * giới hạn độ dài khớp đúng kích thước cột trong bảng shipping_addresses.
     */
    public static function validationRules(): array
    {
        return [
            'receiver_name' => 'required|string|max:100',
            'phone' => 'required|string|max:15',
            'province' => 'required|string|max:100',
            'district' => 'required|string|max:100',
            'ward' => 'required|string|max:100',
            'address' => 'required|string|max:255',
            'note' => 'nullable|string|max:255',
            'is_default' => 'nullable|boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Đặt địa chỉ này làm mặc định, bỏ mặc định các địa chỉ khác của cùng user.
     */
    public function setAsDefault(): void
    {
        static::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }
}
