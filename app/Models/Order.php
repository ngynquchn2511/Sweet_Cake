<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    /**
     * Bộ giá trị trạng thái dùng chung cho mọi controller (web + API) - khớp enum của bảng orders.
     */
    public const ORDER_STATUSES = ['pending', 'confirmed', 'processing', 'shipping', 'completed', 'cancelled', 'refunded'];
    public const PAYMENT_STATUSES = ['unpaid', 'paid', 'refunded'];

    protected $fillable = [
        'user_id',
        'shipping_address_id',
        'promotion_id',
        'order_code',
        'subtotal',
        'shipping_fee',
        'discount_amount',
        'total_amount',
        'order_status',
        'payment_method',
        'payment_status',
        'note',
        'cancelled_reason',
    ];

    // Relationship: Order belongs to User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    //  Relationship: Order has many OrderItems
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * mỗi đơn hàng có thể có nhiều bản ghi thanh toán
     */
    public function payments()
    {
        return $this->hasMany(Payment::class); // hoặc hasOne, belongsTo,… tùy cấu trúc
    }

    //  Relationship: Order belongs to ShippingAddress
    public function shippingAddress()
    {
        return $this->belongsTo(ShippingAddress::class);
    }

    //  Relationship: Order belongs to Promotion
    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }

    // for a single-payment setup
    public function payment()
    {
        return $this->hasOne(Payment::class); // or hasMany()->first() etc.
    }

    /**
     * Đơn hàng chỉ có thể huỷ khi chưa được xác nhận/xử lý.
     */
    public function canCancel(): bool
    {
        return in_array($this->order_status, ['pending', 'confirmed'], true);
    }
}