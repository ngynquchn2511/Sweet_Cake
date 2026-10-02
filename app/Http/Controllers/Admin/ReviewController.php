<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\Review;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReviewController extends Controller
{
    /**
     * Display reviews list
     */
    public function index(Request $request)
    {
        $query = Review::with(['user', 'product']);

        // Search by product name or customer name
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->whereHas('product', function($q2) use ($request) {
                    $q2->where('name', 'like', "%{$request->search}%");
                })
                ->orWhereHas('user', function($q2) use ($request) {
                    $q2->where('full_name', 'like', "%{$request->search}%");
                })
                ->orWhere('comment', 'like', "%{$request->search}%");
            });
        }

        // Filter by rating
        if ($request->rating) {
            $query->where('rating', $request->rating);
        }

        // Filter by status
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Sort (whitelist cột để tránh lỗi SQL từ input tuỳ ý)
        $sortBy = in_array($request->sort_by, ['created_at', 'rating', 'status'], true) ? $request->sort_by : 'created_at';
        $sortOrder = $request->sort_order === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortOrder);

        $reviews = $query->paginate($request->per_page ?? 15);

        // Statistics
        $stats = [
            'total' => Review::count(),
            'pending' => Review::where('status', 'pending')->count(),
            'approved' => Review::where('status', 'approved')->count(),
            'rejected' => Review::where('status', 'rejected')->count(),
            'avg_rating' => round(Review::where('status', 'approved')->avg('rating') ?? 0, 1),
        ];

        return Inertia::render('Admin/Reviews/Index', [
            'reviews' => $reviews,
            'stats' => $stats,
            'filters' => $request->only(['search', 'rating', 'status', 'sort_by', 'sort_order']),
        ]);
    }

    /**
     * Show review detail
     */
    public function show($id)
    {
        $review = Review::with(['user', 'product', 'order', 'reviewer:id,full_name'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $review,
        ]);
    }

    /**
     * Approve a review ("Duyệt nhanh")
     */
    public function approve(Request $request, $id)
    {
        $review = $this->moderate($id, 'approved');

        return $this->respond($request, $review, 'Đã duyệt đánh giá');
    }

    /**
     * Reject a review ("Từ chối nhanh")
     */
    public function reject(Request $request, $id)
    {
        $review = $this->moderate($id, 'rejected');

        return $this->respond($request, $review, 'Đã từ chối đánh giá');
    }

    /**
     * Update review status
     */
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,approved,rejected',
        ]);

        $review = $this->moderate($id, $validated['status']);

        return $this->respond($request, $review, 'Cập nhật trạng thái đánh giá thành công!');
    }

    /**
     * Đổi trạng thái đánh giá, lưu vết admin thực hiện và ghi nhật ký hoạt động.
     */
    private function moderate($id, string $status): Review
    {
        $review = Review::findOrFail($id);
        $oldStatus = $review->status;

        $review->update([
            'status' => $status,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        AdminLog::record('update_status', 'reviews', $review->id, ['status' => $oldStatus], ['status' => $status]);

        return $review;
    }

    private function respond(Request $request, Review $review, string $message)
    {
        if ($request->is('api/*') || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $review,
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Delete review
     */
    public function destroy($id)
    {
        $review = Review::findOrFail($id);

        if ($review->images) {
            foreach ($review->images as $image) {
                if (file_exists(public_path($image))) {
                    unlink(public_path($image));
                }
            }
        }

        AdminLog::record('delete', 'reviews', $review->id, $review->only(['product_id', 'user_id', 'rating', 'status']));

        $review->delete();

        if (request()->is('api/*') || request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Xóa đánh giá thành công!']);
        }

        return back()->with('success', 'Xóa đánh giá thành công!');
    }
}