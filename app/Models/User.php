<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'full_name',
        'email',
        'phone',
        'password',
        'avatar',
        'role',
        'status',
        'email_verified_at',
        'reset_token',
        'last_login',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'reset_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Dọn dữ liệu cá nhân khi xoá tài khoản thay vì để lại bản ghi mồ côi:
     * - Xoá giỏ hàng, danh sách yêu thích, đánh giá (kèm ảnh) và địa chỉ chưa dùng cho đơn nào.
     * - Giữ lại đơn hàng + địa chỉ đã dùng cho đơn (phục vụ đối soát) nhưng tách user_id về NULL.
     * - Tin nhắn hỏi-đáp giữ lại cho admin tham khảo, tách user_id về NULL.
     */
    protected static function booted(): void
    {
        static::deleting(function (User $user) {
            if ($user->cart) {
                $user->cart->cartItems()->delete();
                $user->cart->delete();
            }

            $user->wishlists()->delete();

            foreach ($user->reviews as $review) {
                foreach ((array) $review->images as $image) {
                    if ($image && file_exists(public_path($image))) {
                        @unlink(public_path($image));
                    }
                }
                $review->delete();
            }

            $usedAddressIds = $user->orders()->whereNotNull('shipping_address_id')->pluck('shipping_address_id');
            $user->shippingAddresses()->whereNotIn('id', $usedAddressIds)->delete();
            $user->shippingAddresses()->update(['user_id' => null, 'is_default' => false]);

            $user->orders()->update(['user_id' => null]);
            Message::where('user_id', $user->id)->update(['user_id' => null]);
            $user->tokens()->delete();
        });
    }

    /**
     * Thu hồi mọi phiên đăng nhập khác của user sau khi đổi mật khẩu:
     * xoá token API (Sanctum) và các session web khác (khi SESSION_DRIVER=database).
     */
    public function revokeOtherSessions(?string $keepSessionId = null, ?int $keepTokenId = null): void
    {
        $this->tokens()->when($keepTokenId, fn ($q) => $q->where('id', '!=', $keepTokenId))->delete();

        if (config('session.driver') === 'database') {
            \Illuminate\Support\Facades\DB::table(config('session.table', 'sessions'))
                ->where('user_id', $this->id)
                ->when($keepSessionId, fn ($q) => $q->where('id', '!=', $keepSessionId))
                ->delete();
        }
    }

    // Relationships
    public function cart()
    {
        return $this->hasOne(Cart::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function shippingAddresses()
    {
        return $this->hasMany(ShippingAddress::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function adminLogs()
    {
        return $this->hasMany(AdminLog::class);
    }

    // Scopes
    public function scopeCustomers($query)
    {
        return $query->where('role', 'customer');
    }

    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeBanned($query)
    {
        return $query->where('status', 'banned');
    }

    // Helper Methods
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isCustomer()
    {
        return $this->role === 'customer';
    }

    public function isActive()
    {
        return $this->status === 'active';
    }

    public function isBanned()
    {
        return $this->status === 'banned';
    }

    public function getTotalOrders()
    {
        return $this->orders()->count();
    }

    public function getTotalSpent()
    {
        return $this->orders()->where('order_status', 'completed')->sum('total_amount');
    }

    public function getCompletedOrders()
    {
        return $this->orders()->where('order_status', 'completed')->count();
    }

    public function getDefaultShippingAddress()
    {
        return $this->shippingAddresses()->where('is_default', true)->first();
    }

    public function hasVerifiedEmail()
    {
        return !is_null($this->email_verified_at);
    }
}