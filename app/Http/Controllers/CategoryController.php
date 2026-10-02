<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Get all categories (public)
     */
    public function index()
    {
        $categories = Category::where('status', 'active')
            ->orderBy('display_order', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Get category detail
     */
    public function show($id)
    {
        $category = Category::with(['products' => function($query) {
            $query->where('status', 'active')->limit(10);
        }])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $category
        ]);
    }

    /**
     * Store new category (Admin only)
     */
    public function store(Request $request)
    {
        // Dùng chung luật validate với Admin\CategoryController (BUG-035)
        $request->validate(Category::validationRules());

        // Upload image
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/categories'), $imageName);
            $imagePath = 'uploads/categories/' . $imageName;
        }

        $category = Category::create([
            'name' => $request->name,
            'slug' => $request->slug ?: Category::uniqueSlug($request->name),
            'description' => $request->description,
            'image' => $imagePath ?? null,
            'status' => $request->status,
            'display_order' => $request->display_order ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thêm danh mục thành công',
            'data' => $category
        ], 201);
    }

    /**
     * Update category (Admin only)
     */
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        // Dùng chung luật validate với Admin\CategoryController (BUG-035)
        $request->validate(Category::validationRules((int) $id));

        // Upload new image if provided
        if ($request->hasFile('image')) {
            if ($category->image && file_exists(public_path($category->image))) {
                unlink(public_path($category->image));
            }

            $image = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/categories'), $imageName);
            $imagePath = 'uploads/categories/' . $imageName;
        }

        $category->update([
            'name' => $request->name,
            'slug' => $request->slug ?: Category::uniqueSlug($request->name, (int) $id),
            'description' => $request->description,
            'image' => $imagePath ?? $category->image,
            'status' => $request->status,
            'display_order' => $request->display_order ?? $category->display_order,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật danh mục thành công',
            'data' => $category
        ]);
    }

    /**
     * Delete category (Admin only)
     */
    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        // Check if category has products
        if ($category->products()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa danh mục có sản phẩm'
            ], 422);
        }

        if ($category->image && file_exists(public_path($category->image))) {
            unlink(public_path($category->image));
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa danh mục thành công'
        ]);
    }

    /**
     * Quickly toggle/set category status (Admin only)
     */
    public function updateStatus(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:active,inactive',
        ]);

        $category->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái danh mục thành công',
            'data' => $category,
        ]);
    }

    /**
     * Bulk update display_order for drag-and-drop reordering (Admin only)
     */
    public function updateOrder(Request $request)
    {
        $request->validate([
            'categories' => 'required|array',
            'categories.*.id' => 'required|exists:categories,id',
            'categories.*.display_order' => 'required|integer|min:0',
        ]);

        foreach ($request->categories as $item) {
            Category::where('id', $item['id'])->update(['display_order' => $item['display_order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thứ tự hiển thị thành công',
        ]);
    }
}
