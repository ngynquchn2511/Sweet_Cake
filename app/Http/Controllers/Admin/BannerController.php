<?php
// app/Http/Controllers/Admin/BannerController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BannerController extends Controller
{
    /**
     * Display a listing of banners
     */
    public function index(Request $request)
    {
        $query = Banner::query();

        // Search by title
        if ($request->search) {
            $query->where('title', 'like', "%{$request->search}%");
        }

        // Filter by position
        if ($request->position) {
            $query->where('position', $request->position);
        }

        // Filter by status
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Sort
        // Sort (whitelist cột để tránh lỗi SQL từ input tuỳ ý)
        $sortBy = in_array($request->sort_by, ['display_order', 'created_at', 'title', 'position', 'status'], true) ? $request->sort_by : 'display_order';
        $sortOrder = $request->sort_order === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sortBy, $sortOrder);

        $banners = $query->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $banners
        ]);
    }

    /**
     * Store a newly created banner
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:200',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'link' => 'nullable|url|max:500',
            'position' => 'required|in:homepage_main,homepage_sub,sidebar,footer',
            'display_order' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        // Upload image
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/banners'), $imageName);
            $imagePath = 'uploads/banners/' . $imageName;
        }

        // Get next display order if not provided
        $displayOrder = $request->display_order;
        if (!$displayOrder) {
            $maxOrder = Banner::where('position', $request->position)->max('display_order');
            $displayOrder = $maxOrder ? $maxOrder + 1 : 1;
        }

        $banner = Banner::create([
            'title' => $request->title,
            'image' => $imagePath,
            'link' => $request->link,
            'position' => $request->position,
            'display_order' => $displayOrder,
            'status' => $request->status,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tạo banner thành công',
            'data' => $banner
        ], 201);
    }

    /**
     * Display the specified banner
     */
    public function show($id)
    {
        $banner = Banner::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $banner
        ]);
    }

    /**
     * Update the specified banner
     */
    public function update(Request $request, $id)
    {
        $banner = Banner::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:200',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'link' => 'nullable|url|max:500',
            'position' => 'required|in:homepage_main,homepage_sub,sidebar,footer',
            'display_order' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        // Upload new image if provided
        if ($request->hasFile('image')) {
            // Delete old image
            if ($banner->image && file_exists(public_path($banner->image))) {
                unlink(public_path($banner->image));
            }

            $image = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/banners'), $imageName);
            $imagePath = 'uploads/banners/' . $imageName;
        } else {
            $imagePath = $banner->image;
        }

        $banner->update([
            'title' => $request->title,
            'image' => $imagePath,
            'link' => $request->link,
            'position' => $request->position,
            'display_order' => $request->display_order ?? $banner->display_order,
            'status' => $request->status,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật banner thành công',
            'data' => $banner
        ]);
    }

    /**
     * Remove the specified banner
     */
    public function destroy($id)
    {
        $banner = Banner::findOrFail($id);

        // Delete image file
        if ($banner->image && file_exists(public_path($banner->image))) {
            unlink(public_path($banner->image));
        }

        $banner->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa banner thành công'
        ]);
    }

    /**
     * Toggle banner status
     */
    public function toggleStatus($id)
    {
        $banner = Banner::findOrFail($id);

        $newStatus = $banner->status === 'active' ? 'inactive' : 'active';
        $banner->update(['status' => $newStatus]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái thành công',
            'data' => $banner
        ]);
    }

    /**
     * Update display order for multiple banners
     */
    public function updateOrder(Request $request)
    {
        $request->validate([
            'banners' => 'required|array',
            'banners.*.id' => 'required|exists:banners,id',
            'banners.*.display_order' => 'required|integer|min:0',
        ]);

        foreach ($request->banners as $bannerData) {
            Banner::where('id', $bannerData['id'])
                ->update(['display_order' => $bannerData['display_order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thứ tự hiển thị thành công'
        ]);
    }

    /**
     * Get banners by position
     */
    public function getByPosition($position)
    {
        $banners = Banner::where('position', $position)
            ->where('status', 'active')
            ->orderBy('display_order', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $banners
        ]);
    }

    /**
     * Get active banners (for frontend)
     */
    public function getActive(Request $request)
    {
        $query = Banner::active();

        if ($request->position) {
            $query->byPosition($request->position);
        }

        $banners = $query->ordered()->get();

        return response()->json([
            'success' => true,
            'data' => $banners
        ]);
    }

    /**
     * Duplicate banner
     */
    public function duplicate($id)
    {
        $banner = Banner::findOrFail($id);

        // Copy image (nếu file gốc không còn thì dùng lại đường dẫn cũ thay vì trỏ tới file không tồn tại)
        $newImage = $banner->image;
        if ($banner->image && file_exists(public_path($banner->image))) {
            $newImageName = time() . '_' . uniqid() . '.' . pathinfo($banner->image, PATHINFO_EXTENSION);
            copy(public_path($banner->image), public_path('uploads/banners/' . $newImageName));
            $newImage = 'uploads/banners/' . $newImageName;
        }

        $newBanner = $banner->replicate();
        $newBanner->title = $banner->title . ' (Copy)';
        $newBanner->image = $newImage;
        $newBanner->status = 'inactive';
        $newBanner->display_order = Banner::where('position', $banner->position)->max('display_order') + 1;
        $newBanner->save();

        return response()->json([
            'success' => true,
            'message' => 'Sao chép banner thành công',
            'data' => $newBanner
        ], 201);
    }

    /**
     * Get banner statistics
     */
    public function getStatistics()
    {
        $stats = [
            'total_banners' => Banner::count(),
            'active_banners' => Banner::where('status', 'active')->count(),
            'inactive_banners' => Banner::where('status', 'inactive')->count(),
            'by_position' => Banner::selectRaw('position, COUNT(*) as count')
                ->groupBy('position')
                ->get()
                ->pluck('count', 'position'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get available positions
     */
    public function getPositions()
    {
        $positions = [
            'homepage_main' => 'Banner chính trang chủ',
            'homepage_sub' => 'Banner phụ trang chủ',
            'sidebar' => 'Banner sidebar',
            'footer' => 'Banner footer',
        ];

        return response()->json([
            'success' => true,
            'data' => $positions
        ]);
    }
}