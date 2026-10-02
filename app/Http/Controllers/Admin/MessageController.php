<?php
// app/Http/Controllers/Admin/MessageController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Mail\MessageRepliedMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class MessageController extends Controller
{
    /**
     * Danh sách tin nhắn
     */
    public function index(Request $request)
    {
        $query = Message::with('user')->orderBy('created_at', 'desc');

        // Search
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('subject', 'like', "%{$request->search}%")
                  ->orWhere('content', 'like', "%{$request->search}%")
                  ->orWhere('guest_name', 'like', "%{$request->search}%")
                  ->orWhere('guest_email', 'like', "%{$request->search}%")
                  ->orWhereHas('user', function ($q2) use ($request) {
                      $q2->where('full_name', 'like', "%{$request->search}%")
                         ->orWhere('email', 'like', "%{$request->search}%");
                  });
            });
        }

        // Filter by status
        if ($request->status && in_array($request->status, ['unread', 'read', 'replied'])) {
            $query->where('status', $request->status);
        }

        $messages = $query->paginate($request->per_page ?? 15);

        return Inertia::render('Admin/Messages/Index', [
            'messages' => $messages,
            'filters'  => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Chi tiết tin nhắn (tự mark read)
     */
    public function show($id)
    {
        $message = Message::with('user')->findOrFail($id);

        if ($message->status === 'unread') {
            $message->update(['status' => 'read']);
        }

        return Inertia::render('Admin/Messages/Detail', [
            'message' => $message,
        ]);
    }

    /**
     * Admin phản hồi
     */
    public function reply(Request $request, $id)
    {
        $message = Message::findOrFail($id);

        $request->validate([
            'admin_reply' => ['required', 'string'],
        ]);

        $message->update([
            'admin_reply' => $request->admin_reply,
            'status'      => 'replied',
        ]);

        // Gửi email thông báo cho khách hàng (nếu có địa chỉ email)
        $recipientEmail = $message->user?->email ?? $message->guest_email;
        if ($recipientEmail) {
            Mail::to($recipientEmail)->send(new MessageRepliedMail($message->fresh()));
        }

        return back()->with('success', 'Đã phản hồi tin nhắn!');
    }

    /**
     * Xóa tin nhắn
     */
    public function destroy($id)
    {
        Message::findOrFail($id)->delete();

        return redirect()
            ->route('admin.messages.index')
            ->with('success', 'Đã xóa tin nhắn!');
    }

    /**
     * Mark all unread → read
     */
    public function markAllRead()
    {
        Message::where('status', 'unread')->update(['status' => 'read']);

        return back()->with('success', 'Đã đánh dấu tất cả đã đọc!');
    }
}