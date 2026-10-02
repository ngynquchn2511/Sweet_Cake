<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use Inertia\Inertia;
use App\Services\OrderPlacementService;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    // Trong PaymentController.php
    public function createPayment(Request $request, OrderPlacementService $placement)
    {
        $request->validate([
            'order_id' => 'nullable|exists:orders,id',
            'promotion_code' => 'nullable|string|max:50',
        ]);

        $userId = $request->user()->id ?? auth()->id();

        if ($request->filled('order_id')) {
            // Thanh toán lại 1 đơn đã có: phải thuộc về chính người đang thanh toán và chưa được thanh toán
            $order = Order::where('id', $request->order_id)
                ->where('user_id', $userId)
                ->where('payment_status', '!=', 'paid')
                ->firstOrFail();
        } else {
            // Tạo đơn hàng thật từ giỏ trước khi chuyển sang MoMo, để momoReturn/momoIPN tra lại đúng đơn
            try {
                $order = $placement->placeFromCart($userId, 'momo', null, $request->promotion_code);
            } catch (ValidationException $e) {
                return response()->json([
                    'success' => false,
                    'message' => collect($e->errors())->flatten()->first(),
                    'errors' => $e->errors(),
                ], 422);
            }
        }

        try {
            // Lấy key từ .env
            $partnerCode = env('MOMO_PARTNER_CODE');
            $accessKey = env('MOMO_ACCESS_KEY');
            $secretKey = env('MOMO_SECRET_KEY');

            // orderId gửi sang MoMo PHẢI khớp order_code thật để momoReturn/momoIPN tra lại đúng đơn
            $orderId = $order->order_code;
            $requestId = (string) time();
            $amount = (string) round($order->total_amount);
            $orderInfo = "Thanh toan don hang Sweet Bakery - {$order->order_code}";
            $redirectUrl = url('/momo-return');
            $ipnUrl = url('/api/momo-ipn');
            $extraData = "";
            $requestType = "captureWallet";

            // CHUỖI BĂM CHUẨN THEO LỖI 11007 (Đã đổi returnUrl -> redirectUrl)
            $rawHash = "accessKey=" . $accessKey .
                "&amount=" . $amount .
                "&extraData=" . $extraData .
                "&ipnUrl=" . $ipnUrl .
                "&orderId=" . $orderId .
                "&orderInfo=" . $orderInfo .
                "&partnerCode=" . $partnerCode .
                "&redirectUrl=" . $redirectUrl .
                "&requestId=" . $requestId .
                "&requestType=" . $requestType;

            $signature = hash_hmac("sha256", $rawHash, $secretKey);

            $data = [
                'partnerCode' => $partnerCode,
                'partnerName' => "Test Store",
                'storeId'     => "MomoTestStore",
                'requestId'   => $requestId,
                'amount'      => $amount,
                'orderId'     => $orderId,
                'orderInfo'   => $orderInfo,
                'redirectUrl' => $redirectUrl,
                'ipnUrl'      => $ipnUrl,
                'extraData'   => $extraData,
                'requestType' => $requestType,
                'signature'   => $signature,
                'lang'        => 'vi'
            ];

            $response = Http::withHeaders([
                'Content-Type' => 'application/json; charset=UTF-8',
            ])->withBody(
                json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'application/json'
            )->post("https://test-payment.momo.vn/v2/gateway/api/create");

            $result = $response->json();
            if (isset($result['payUrl'])) {
                // Trang giỏ hàng gọi bằng axios nên cần nhận payUrl dạng JSON rồi tự chuyển trang
                return $request->wantsJson() || $request->ajax()
                    ? response()->json(['success' => true, 'payUrl' => $result['payUrl'], 'order_code' => $order->order_code])
                    : redirect($result['payUrl']);
            }

            return response()->json(array_merge((array) $result, ['order_code' => $order->order_code]), 502);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    // PaymentController.php
    public function momoIPN(Request $request)
    {
        // Xác thực chữ ký để đảm bảo request thực sự đến từ MoMo, tránh giả mạo "đã thanh toán"
        if (!$this->hasValidMomoSignature($request)) {
            Log::warning('MoMo IPN chữ ký không hợp lệ', ['orderId' => $request->orderId]);
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        if ((string) $request->resultCode === '0') {
            $this->markOrderPaid($request->orderId);
        }

        // MoMo yêu cầu trả về 204 hoặc JSON trống để xác nhận đã nhận tin
        return response()->noContent();
    }

    public function momoReturn(Request $request)
    {
        // Lấy mã đơn hàng MoMo gửi về (MoMo gọi là orderId)
        $order = Order::where('order_code', $request->orderId)
            ->where('user_id', auth()->id())
            ->first();

        if (!$order) {
            abort(404, 'Không tìm thấy đơn hàng với mã: ' . $request->orderId);
        }

        $validSignature = $this->hasValidMomoSignature($request);
        $success = $validSignature && (string) $request->resultCode === '0';

        // Trên môi trường không nhận được IPN (localhost), cập nhật ngay tại redirect nếu chữ ký hợp lệ
        if ($success) {
            $order = $this->markOrderPaid($order->order_code) ?? $order;
        }

        return Inertia::render('Checkout/PaymentResult', [
            'success' => $success,
            'order' => $order->fresh(),
            'message' => $validSignature ? $request->message : 'Chữ ký phản hồi từ MoMo không hợp lệ.',
        ]);
    }

    /**
     * Tính lại chữ ký theo đặc tả MoMo (dùng chung cho IPN và redirect) và so sánh an toàn.
     */
    private function hasValidMomoSignature(Request $request): bool
    {
        $rawHash = "accessKey=" . env('MOMO_ACCESS_KEY') .
            "&amount=" . $request->amount .
            "&extraData=" . $request->extraData .
            "&message=" . $request->message .
            "&orderId=" . $request->orderId .
            "&orderInfo=" . $request->orderInfo .
            "&orderType=" . $request->orderType .
            "&partnerCode=" . $request->partnerCode .
            "&payType=" . $request->payType .
            "&requestId=" . $request->requestId .
            "&responseTime=" . $request->responseTime .
            "&resultCode=" . $request->resultCode .
            "&transId=" . $request->transId;

        $expectedSignature = hash_hmac("sha256", $rawHash, (string) env('MOMO_SECRET_KEY'));

        return hash_equals($expectedSignature, (string) $request->signature);
    }

    private function markOrderPaid(?string $orderCode): ?Order
    {
        $order = Order::where('order_code', $orderCode)->first();

        if ($order && $order->payment_status !== 'paid') {
            $order->update([
                'payment_status' => 'paid',
                'order_status' => 'processing', // Chuyển sang trạng thái đang xử lý/làm bánh
            ]);
        }

        return $order;
    }
}
