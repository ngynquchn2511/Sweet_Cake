<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'order_id',
        'rating',
        'comment',
        'images',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'images' => 'array',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Admin đã duyệt/từ chối đánh giá này.
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // Relationship: Review belongs to User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relationship: Review belongs to Product
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Relationship: Review belongs to Order
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}