<?php
// app/Http/Controllers/MessageController.php  (Customer side – public)

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MessageController extends Controller
{
    /**
     * Khách hàng gửi tin nhắn mới (không cần auth)
     * POST /api/messages
     */
    public function store(Request $request): JsonResponse
    {
        $isLoggedIn = auth()->check();

        $request->validate([
            'guest_name'  => [$isLoggedIn ? 'nullable' : 'required', 'nullable', 'string', 'max:100'],
            'guest_email' => [$isLoggedIn ? 'nullable' : 'required', 'nullable', 'email', 'max:150'],
            'guest_phone' => ['nullable', 'string', 'max:20'],
            'subject'     => ['required', 'string', 'max:200'],
            'content'     => ['required', 'string', 'max:2000'],
        ]);

        $data = $request->only(['guest_name', 'guest_email', 'guest_phone', 'subject', 'content']);

        // Nếu đã login → gán user_id, không cần guest fields
        if (auth()->check()) {
            $data['user_id']      = auth()->id();
            $data['guest_name']   = null;
            $data['guest_email']  = null;
            $data['guest_phone']  = null;
        }

        Message::create($data);

        return response()->json(['message' => 'Tin nhắn đã gửi thành công!'], 201);
    }

    /**
     * Khách hàng đã đăng nhập xem lại tin nhắn mình đã gửi và phản hồi của admin
     * GET /api/customer/messages
     */
    public function myMessages(Request $request)
    {
        $messages = Message::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(15);

        if (!$request->wantsJson() && !$request->is('api/*')) {
            // Trang web: hiển thị cả tin nhắn hỏi-đáp lẫn lịch sử form "Liên hệ" (khớp theo email tài khoản)
            return \Inertia\Inertia::render('Messages/Index', [
                'messages' => $messages,
                'contacts' => ContactMessage::where('email', $request->user()->email)
                    ->orderByDesc('created_at')
                    ->get(),
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $messages,
        ]);
    }
}