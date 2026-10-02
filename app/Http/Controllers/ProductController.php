<?php
// app/Http/Controllers/ProductController.php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Get all products (for customer - public)
     */
    public function index(Request $request)
    {
    // 1. Khởi tạo query (Chưa thực thi ngay)
    $query = Product::with('category')->where('status', 'active');

    // 2. Lọc theo tên bánh (Search)
    if ($request->filled('search')) {
        $query->where('name', 'like', '%' . $request->search . '%');
    }

    // 3. Lọc theo danh mục
    // Trang web gửi tham số "category", API gửi "category_id" - nhận cả hai
    $categoryId = $request->input('category_id', $request->input('category'));
    if (!empty($categoryId)) {
        $query->where('category_id', $categoryId);
    }

    // 4. Lọc theo khoảng giá
    if ($request->filled('min_price')) {
        $query->where('price', '>=', $request->min_price);
    }
    if ($request->filled('max_price')) {
        $query->where('price', '<=', $request->max_price);
    }

    // 5. Sắp xếp (whitelist cột để tránh lỗi SQL từ input tuỳ ý)
    $allowedSortColumns = ['name', 'price', 'created_at', 'view_count', 'sold_count'];
    if ($request->filled('sort_by') && in_array($request->sort_by, $allowedSortColumns, true)) {
        $sortOrder = $request->sort_order === 'desc' ? 'desc' : 'asc';
        $query->orderBy($request->sort_by, $sortOrder);
    } else {
        $query->latest();
    }

    // 6. Thực thi Phân trang (CHỈ GỌI 1 LẦN DUY NHẤT Ở ĐÂY)
    // withQueryString() cực kỳ quan trọng để giữ các filter khi nhấn sang trang 2, 3...
    $products = $query->paginate($request->per_page ?? 12)->withQueryString();

    // 7. Trả về dữ liệu
    if ($request->wantsJson() || $request->is('api/*')) {
        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    return \Inertia\Inertia::render('Customer/Products/Index', [
        'products' => $products,
        'categories' => \App\Models\Category::where('status', 'active')->orderBy('display_order')->get(['id', 'name', 'status']),
        'filters' => array_merge(
            $request->only(['search', 'min_price', 'max_price', 'sort_by']),
            ['category' => $categoryId]
        ),
    ]);
    }

    /**
     * Get product detail by slug or id (for customer - public)
     */
    public function show(Request $request, $slug)
    {
        $product = Product::with(['category', 'reviews' => function($query) {
            $query->where('status', 'approved')->with('user:id,full_name')->latest();
        }])
        ->where(function ($query) use ($slug) {
            $query->where('slug', $slug);
            if (ctype_digit((string) $slug)) {
                $query->orWhere('id', (int) $slug);
            }
        })
        ->where('status', 'active')
        ->firstOrFail();

        // Increment view count
        $product->incrementViewCount();

        // Điểm trung bình chỉ tính trên các đánh giá đã được duyệt
        $product->setAttribute('avg_rating', round((float) $product->reviews->avg('rating'), 1));
        $product->setAttribute('review_count', $product->reviews->count());

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => $product
            ]);
        }

        $related = Product::where('status', 'active')
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->latest()
            ->take(4)
            ->get();

        return \Inertia\Inertia::render('Customer/Products/Show', [
            'product' => $product,
            'relatedProducts' => $related,
            'isInWishlist' => $request->user()
                ? \App\Models\Wishlist::isInWishlist($request->user()->id, $product->id)
                : false,
        ]);
    }

    /**
     * Store new product (Admin only)
     */
    public function store(Request $request)
    {
        $request->validate(Product::validationRules());

        // Upload image
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/products'), $imageName);
            $imagePath = 'uploads/products/' . $imageName;
        }

        $product = Product::create([
            'category_id' => $request->category_id,
            'name' => $request->name,
            'slug' => $request->slug ?: Product::uniqueSlug($request->name),
            'price' => $request->price,
            'discount_price' => $request->discount_price,
            'description' => $request->description,
            'image' => $imagePath ?? null,
            'stock' => $request->stock,
            'unit' => $request->unit,
            'weight' => $request->weight ?? 0,
            'status' => $request->status,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thêm sản phẩm thành công',
            'data' => $product
        ], 201);
    }

    /**
     * Update product (Admin only)
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate(Product::validationRules((int) $id));

        // Upload new image if provided
        if ($request->hasFile('image')) {
            // Delete old image
            if ($product->image && file_exists(public_path($product->image))) {
                unlink(public_path($product->image));
            }

            $image = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/products'), $imageName);
            $imagePath = 'uploads/products/' . $imageName;
        }

        $product->update([
            'category_id' => $request->category_id,
            'name' => $request->name,
            'slug' => $request->slug ?: Product::uniqueSlug($request->name, (int) $id),
            'price' => $request->price,
            'discount_price' => $request->discount_price,
            'description' => $request->description,
            'image' => $imagePath ?? $product->image,
            'stock' => $request->stock,
            'unit' => $request->unit ?? $product->unit,
            'weight' => $request->weight ?? $product->weight,
            'status' => $request->status,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật sản phẩm thành công',
            'data' => $product
        ]);
    }

    /**
     * Delete product (Admin only)
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        // Delete image
        if ($product->image && file_exists(public_path($product->image))) {
            unlink(public_path($product->image));
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa sản phẩm thành công'
        ]);
    }

    /**
     * Quickly toggle/set product status (Admin only)
     */
    public function updateStatus(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:active,inactive',
        ]);

        $product->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái sản phẩm thành công',
            'data' => $product,
        ]);
    }

    /**
     * Duplicate a product to quickly create a variant (Admin only)
     */
    public function duplicate($id)
    {
        $product = Product::findOrFail($id);

        $baseSlug = $product->slug . '-copy';
        $slug = $baseSlug;
        $counter = 1;
        while (Product::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        $newProduct = $product->replicate();
        $newProduct->name = $product->name . ' (Copy)';
        $newProduct->slug = $slug;
        $newProduct->status = 'inactive';
        $newProduct->sold_count = 0;
        $newProduct->view_count = 0;
        $newProduct->save();

        return response()->json([
            'success' => true,
            'message' => 'Sao chép sản phẩm thành công',
            'data' => $newProduct,
        ], 201);
    }
}
