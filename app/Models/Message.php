<?php
// app/Models/Message.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'guest_name',
        'guest_email',
        'guest_phone',
        'subject',
        'content',
        'admin_reply',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Lấy tên người gửi (login hoặc khách)
    public function getSenderNameAttribute()
    {
        return $this->user ? $this->user->full_name : ($this->guest_name ?? 'Khách ẩn danh');
    }

    public function getSenderEmailAttribute()
    {
        return $this->user ? $this->user->email : ($this->guest_email ?? 'N/A');
    }
}