<?php
// app/Http/Controllers/ReviewController.php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    /**
     * Store a new review
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'order_id' => 'required|exists:orders,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:1000',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $userId = Auth::id();

        // Check if order belongs to user and is completed
        $order = Order::where('id', $request->order_id)
            ->where('user_id', $userId)
            ->where('order_status', 'completed')
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Đơn hàng không hợp lệ hoặc chưa hoàn thành'
            ], 422);
        }

        // Check if user already reviewed this product for this order
        $existingReview = Review::where('user_id', $userId)
            ->where('product_id', $request->product_id)
            ->where('order_id', $request->order_id)
            ->first();

        if ($existingReview) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn đã đánh giá sản phẩm này rồi'
            ], 422);
        }

        // Upload images if provided
        $uploadedImages = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('uploads/reviews'), $imageName);
                $uploadedImages[] = 'uploads/reviews/' . $imageName;
            }
        }

        // Create review
        $review = Review::create([
            'user_id' => $userId,
            'product_id' => $request->product_id,
            'order_id' => $request->order_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'images' => !empty($uploadedImages) ? $uploadedImages : null,
            'status' => 'pending', // Admin will approve
        ]);

        $review->load(['user', 'product']);

        return response()->json([
            'success' => true,
            'message' => 'Đánh giá của bạn đã được gửi và đang chờ duyệt',
            'data' => $review
        ], 201);
    }

    /**
     * Update user's review
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:1000',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $review = Review::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$review) {
            return response()->json([
                'success' => false,
                'message' => 'Đánh giá không tồn tại hoặc bạn không có quyền sửa'
            ], 404);
        }

        // Upload new images if provided
        $uploadedImages = $review->images ?? [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('uploads/reviews'), $imageName);
                $uploadedImages[] = 'uploads/reviews/' . $imageName;
            }
        }

        $review->update([
            'rating' => $request->rating,
            'comment' => $request->comment,
            'images' => !empty($uploadedImages) ? $uploadedImages : null,
            'status' => 'pending', // Reset to pending after edit
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật đánh giá thành công',
            'data' => $review
        ]);
    }

    /**
     * Delete user's review
     */
    public function destroy($id)
    {
        $review = Review::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$review) {
            return response()->json([
                'success' => false,
                'message' => 'Đánh giá không tồn tại hoặc bạn không có quyền xóa'
            ], 404);
        }

        // Delete images
        if ($review->images) {
            foreach ($review->images as $image) {
                if (file_exists(public_path($image))) {
                    unlink(public_path($image));
                }
            }
        }

        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa đánh giá thành công'
        ]);
    }

    /**
     * Get reviews for a product
     */
    public function getProductReviews($productId)
    {
        $reviews = Review::where('product_id', $productId)
            ->where('status', 'approved')
            ->with(['user' => function($query) {
                $query->select('id', 'full_name', 'avatar');
            }])
            ->latest()
            ->paginate(10);

        // Calculate average rating
        $averageRating = Review::where('product_id', $productId)
            ->where('status', 'approved')
            ->avg('rating');

        // Count by rating
        $ratingCounts = Review::where('product_id', $productId)
            ->where('status', 'approved')
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->get()
            ->pluck('count', 'rating');

        return response()->json([
            'success' => true,
            'data' => [
                'reviews' => $reviews,
                'statistics' => [
                    'average_rating' => round($averageRating, 1),
                    'total_reviews' => $reviews->total(),
                    'rating_counts' => $ratingCounts,
                ]
            ]
        ]);
    }

    /**
     * Get user's reviews
     */
    public function getUserReviews()
    {
        $reviews = Review::where('user_id', Auth::id())
            ->with(['product' => function($query) {
                $query->select('id', 'name', 'slug', 'image');
            }])
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $reviews
        ]);
    }
}