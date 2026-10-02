<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\AdminLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CategoryController extends Controller
{
    /**
     * Display category list
     */
    public function index(Request $request)
    {
        $query = Category::query();

        // Search
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('slug', 'like', "%{$request->search}%");
            });
        }

        // Filter by status
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Sort (whitelist cột để tránh lỗi SQL từ input tuỳ ý)
        $sortBy = in_array($request->sort_by, ['created_at', 'name', 'display_order', 'status'], true) ? $request->sort_by : 'created_at';
        $sortOrder = $request->sort_order === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // Paginate with product count
        $categories = $query->withCount('products')->paginate($request->per_page ?? 15);

        return Inertia::render('Admin/Categories/Index', [
            'categories' => $categories,
            'filters' => $request->only(['search', 'status', 'sort_by', 'sort_order']),
        ]);
    }

    /**
     * Show create form
     */
    public function create()
    {
        return Inertia::render('Admin/Categories/Form', [
            'category' => null,
        ]);
    }

    /**
     * Store new category
     */
    public function store(Request $request)
    {
        $validated = $request->validate(Category::validationRules());

        // Tự sinh slug duy nhất nếu để trống
        if (empty($validated['slug'])) {
            $validated['slug'] = Category::uniqueSlug($validated['name']);
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/categories'), $imageName);
            $validated['image'] = 'uploads/categories/' . $imageName;
        }

        $category = Category::create($validated);

        AdminLog::create([
            'user_id' => auth()->id(),
            'action' => 'create',
            'table_name' => 'categories',
            'record_id' => $category->id,
            'new_value' => $category->only(['name', 'status']),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect('/admin/categories')
            ->with('success', 'Thêm danh mục thành công!');
    }

    /**
     * Show category detail
     */
    public function show($id)
    {
        $category = Category::withCount('products')->findOrFail($id);

        return Inertia::render('Admin/Categories/Show', [
            'category' => $category,
        ]);
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $category = Category::findOrFail($id);

        return Inertia::render('Admin/Categories/Form', [
            'category' => $category,
        ]);
    }

    /**
     * Update category
     */
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate(Category::validationRules((int) $id));

        // Tự sinh slug duy nhất nếu để trống (bỏ qua chính danh mục đang sửa)
        if (empty($validated['slug'])) {
            $validated['slug'] = Category::uniqueSlug($validated['name'], (int) $id);
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($category->image && file_exists(public_path($category->image))) {
                unlink(public_path($category->image));
            }

            $image = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/categories'), $imageName);
            $validated['image'] = 'uploads/categories/' . $imageName;
        }

        $oldValue = $category->only(['name', 'status']);

        $category->update($validated);

        AdminLog::create([
            'user_id' => auth()->id(),
            'action' => 'update',
            'table_name' => 'categories',
            'record_id' => $category->id,
            'old_value' => $oldValue,
            'new_value' => $category->only(['name', 'status']),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect('/admin/categories')
            ->with('success', 'Cập nhật danh mục thành công!');
    }

    /**
     * Delete category
     */
    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        // Check if category has products
        if ($category->products()->count() > 0) {
            return back()->with('error', 'Không thể xóa danh mục đang có sản phẩm!');
        }

        // Delete image
        if ($category->image && file_exists(public_path($category->image))) {
            unlink(public_path($category->image));
        }

        AdminLog::create([
            'user_id' => auth()->id(),
            'action' => 'delete',
            'table_name' => 'categories',
            'record_id' => $category->id,
            'old_value' => $category->only(['name', 'status']),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $category->delete();

        return redirect('/admin/categories')
            ->with('success', 'Xóa danh mục thành công!');
    }
}