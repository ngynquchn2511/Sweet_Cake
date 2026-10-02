<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\AdminLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    /**
     * Display product list
     */
    public function index(Request $request)
    {
        // 1. Khởi tạo Query với Eager Loading
        $products = Product::with('category')
            // Tìm kiếm
            ->when($request->search, function($q, $search) {
                $q->where(function($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                          ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            // Lọc theo danh mục
            ->when($request->category_id, function($q, $catId) {
                $q->where('category_id', $catId);
            })
            // Lọc theo trạng thái (Giữ lại từ main)
            ->when($request->status, function($q, $status) {
                $q->where('status', $status);
            })
            // Sắp xếp (chỉ cho phép các cột đã whitelist để tránh lỗi SQL từ input tuỳ ý)
            ->orderBy(
                in_array($request->sort_by, ['created_at', 'name', 'price', 'stock', 'status'], true) ? $request->sort_by : 'created_at',
                $request->sort_order === 'asc' ? 'asc' : 'desc'
            )
            // Phân trang
            ->paginate($request->per_page ?? 12)
            ->withQueryString();

        // 2. Lấy danh sách category cho dropdown filter
        $categories = Category::where('status', 'active')
            ->orderBy('name')
            ->get();

        // 3. Render (Lưu ý: Bạn cần xác định đúng đường dẫn là 'Admin/Products/Index' hay 'Products/Index')
        return Inertia::render('Admin/Products/Index', [
            'products' => $products,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category_id', 'status', 'sort_by', 'sort_order']),
        ]);
    }

    /**
     * Show create form
     */
    public function create()
    {
        $categories = Category::where('status', 'active')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Products/Create', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store new product
     */
    public function store(Request $request)
    {
        $validated = $request->validate(Product::validationRules());

        // Tự sinh slug duy nhất nếu để trống
        if (empty($validated['slug'])) {
            $validated['slug'] = Product::uniqueSlug($validated['name']);
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/products'), $imageName);
            $validated['image'] = 'uploads/products/' . $imageName;
        }

        $product = Product::create($validated);

        AdminLog::create([
            'user_id' => auth()->id(),
            'action' => 'create',
            'table_name' => 'products',
            'record_id' => $product->id,
            'new_value' => $product->only(['name', 'price', 'stock', 'status']),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Thêm sản phẩm thành công!');
    }

    /**
     * Show product detail
     */
    public function show($id)
    {
        $product = Product::with('category')->findOrFail($id);

        return Inertia::render('Admin/Products/Show', [
            'product' => $product,
        ]);
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $product = Product::with('category')->findOrFail($id);
        
        $categories = Category::where('status', 'active')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Products/Edit', [
            'product' => $product,
            'categories' => $categories,
        ]);
    }

    /**
     * Update product
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate(Product::validationRules((int) $id));

        // Tự sinh slug duy nhất nếu để trống (bỏ qua chính sản phẩm đang sửa)
        if (empty($validated['slug'])) {
            $validated['slug'] = Product::uniqueSlug($validated['name'], (int) $id);
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($product->image && file_exists(public_path($product->image))) {
                unlink(public_path($product->image));
            }

            $image = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/products'), $imageName);
            $validated['image'] = 'uploads/products/' . $imageName;
        }

        $oldValue = $product->only(['name', 'price', 'stock', 'status']);

        $product->update($validated);

        AdminLog::create([
            'user_id' => auth()->id(),
            'action' => 'update',
            'table_name' => 'products',
            'record_id' => $product->id,
            'old_value' => $oldValue,
            'new_value' => $product->only(['name', 'price', 'stock', 'status']),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Cập nhật sản phẩm thành công!');
    }

    /**
     * Delete product
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        // Delete image
        if ($product->image && file_exists(public_path($product->image))) {
            unlink(public_path($product->image));
        }

        AdminLog::create([
            'user_id' => auth()->id(),
            'action' => 'delete',
            'table_name' => 'products',
            'record_id' => $product->id,
            'old_value' => $product->only(['name', 'price', 'stock', 'status']),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $product->delete();

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Xóa sản phẩm thành công!');
    }
}