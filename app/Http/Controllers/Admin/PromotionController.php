<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use App\Models\AdminLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Str;

class PromotionController extends Controller
{
    /**
     * Display promotion list
     */
    public function index(Request $request)
    {
        $query = Promotion::query();

        // Search by code or description
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('code', 'like', "%{$request->search}%")
                  ->orWhere('description', 'like', "%{$request->search}%");
            });
        }

        // Filter by status
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Filter by discount type
        if ($request->discount_type) {
            $query->where('discount_type', $request->discount_type);
        }

        // Sort (whitelist cột để tránh lỗi SQL từ input tuỳ ý)
        $sortBy = in_array($request->sort_by, ['created_at', 'code', 'start_date', 'end_date', 'used_count'], true) ? $request->sort_by : 'created_at';
        $sortOrder = $request->sort_order === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortOrder);

        $promotions = $query->paginate($request->per_page ?? 15);

        return Inertia::render('Admin/Promotions/Index', [
            'promotions' => $promotions,
            'filters' => $request->only(['search', 'status', 'discount_type']),
        ]);
    }

    /**
     * Show create form
     */
    public function create()
    {
        return Inertia::render('Admin/Promotions/Form', [
            'promotion' => null,
        ]);
    }

    /**
     * Store new promotion
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|unique:promotions,code|max:50',
            'description' => 'nullable|max:500',
            'discount_type' => 'required|in:percent,fixed',
            'discount_value' => 'required|numeric|min:0',
            'min_order_value' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|in:active,inactive',
        ]);

        // Validate discount_value based on type
        if ($validated['discount_type'] === 'percent' && $validated['discount_value'] > 100) {
            return back()->withErrors(['discount_value' => 'Giảm giá phần trăm không được vượt quá 100%']);
        }

        // Convert code to uppercase
        $validated['code'] = strtoupper($validated['code']);
        
        // Set default used_count
        $validated['used_count'] = 0;

        $promotion = Promotion::create($validated);

        AdminLog::create([
            'user_id' => auth()->id(),
            'action' => 'create',
            'table_name' => 'promotions',
            'record_id' => $promotion->id,
            'new_value' => $promotion->only(['code', 'discount_type', 'discount_value', 'status']),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()
            ->route('admin.promotions.index')
            ->with('success', 'Thêm mã khuyến mãi thành công!');
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $promotion = Promotion::findOrFail($id);

        return Inertia::render('Admin/Promotions/Form', [
            'promotion' => $promotion,
        ]);
    }

    /**
     * Update promotion
     */
    public function update(Request $request, $id)
    {
        $promotion = Promotion::findOrFail($id);

        $validated = $request->validate([
            'code' => 'required|max:50|unique:promotions,code,' . $id,
            'description' => 'nullable|max:500',
            'discount_type' => 'required|in:percent,fixed',
            'discount_value' => 'required|numeric|min:0',
            'min_order_value' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|in:active,inactive',
        ]);

        // Validate discount_value based on type
        if ($validated['discount_type'] === 'percent' && $validated['discount_value'] > 100) {
            return back()->withErrors(['discount_value' => 'Giảm giá phần trăm không được vượt quá 100%']);
        }

        // Convert code to uppercase
        $validated['code'] = strtoupper($validated['code']);

        $oldValue = $promotion->only(['code', 'discount_type', 'discount_value', 'status']);

        $promotion->update($validated);

        AdminLog::create([
            'user_id' => auth()->id(),
            'action' => 'update',
            'table_name' => 'promotions',
            'record_id' => $promotion->id,
            'old_value' => $oldValue,
            'new_value' => $promotion->only(['code', 'discount_type', 'discount_value', 'status']),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()
            ->route('admin.promotions.index')
            ->with('success', 'Cập nhật mã khuyến mãi thành công!');
    }

    /**
     * Toggle promotion status
     */
    public function toggleStatus($id)
    {
        $promotion = Promotion::findOrFail($id);
        
        $newStatus = $promotion->status === 'active' ? 'inactive' : 'active';
        $promotion->update(['status' => $newStatus]);

        return back()->with('success', 
            $newStatus === 'active' 
                ? 'Đã kích hoạt mã khuyến mãi!' 
                : 'Đã vô hiệu hóa mã khuyến mãi!'
        );
    }

    /**
     * Delete promotion
     */
    public function destroy($id)
    {
        $promotion = Promotion::findOrFail($id);

        // Check if promotion is being used
        if ($promotion->used_count > 0) {
            return back()->with('error', 'Không thể xóa mã khuyến mãi đã được sử dụng!');
        }

        AdminLog::create([
            'user_id' => auth()->id(),
            'action' => 'delete',
            'table_name' => 'promotions',
            'record_id' => $promotion->id,
            'old_value' => $promotion->only(['code', 'discount_type', 'discount_value', 'status']),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $promotion->delete();

        return redirect()
            ->route('admin.promotions.index')
            ->with('success', 'Xóa mã khuyến mãi thành công!');
    }

    /**
     * Check promotion code validity (for testing)
     */
    public function checkCode(Request $request)
    {
        $request->validate([
            'code' => 'required',
            'order_amount' => 'required|numeric|min:0',
        ]);

        $result = Promotion::validateAndCalculate($request->code, (float) $request->order_amount);

        if (!$result['valid']) {
            $status = $result['promotion'] === null ? 404 : 400;
            return response()->json([
                'valid' => false,
                'message' => $result['message'],
            ], $status);
        }

        return response()->json([
            'valid' => true,
            'message' => $result['message'],
            'promotion' => $result['promotion'],
            'discount_amount' => $result['discount_amount'],
        ]);
    }

    /**
     * Show promotion detail
     */
    public function show($id)
    {
        $promotion = Promotion::withCount('orders')->findOrFail($id);

        if (request()->wantsJson() || request()->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => $promotion,
            ]);
        }

        return Inertia::render('Admin/Promotions/Show', [
            'promotion' => $promotion,
            'usage' => $this->getUsageReport($id)->getData(true)['data'],
        ]);
    }

    /**
     * Duplicate a promotion so admin can quickly create a similar campaign
     */
    public function duplicate($id)
    {
        $promotion = Promotion::findOrFail($id);

        $baseCode = $promotion->code . '-COPY';
        $code = $baseCode;
        $counter = 1;
        while (Promotion::where('code', $code)->exists()) {
            $code = $baseCode . $counter;
            $counter++;
        }

        $newPromotion = $promotion->replicate();
        $newPromotion->code = $code;
        $newPromotion->used_count = 0;
        $newPromotion->status = 'inactive';
        $newPromotion->save();

        AdminLog::record('duplicate', 'promotions', $newPromotion->id, ['source_id' => $promotion->id], $newPromotion->only(['code', 'status']));

        return response()->json([
            'success' => true,
            'message' => 'Sao chép mã khuyến mãi thành công',
            'data' => $newPromotion,
        ], 201);
    }

    /**
     * Generate a random, unused promotion code suggestion
     */
    public function generateCode()
    {
        do {
            $code = 'SALE' . strtoupper(Str::random(6));
        } while (Promotion::where('code', $code)->exists());

        return response()->json([
            'success' => true,
            'code' => $code,
        ]);
    }

    /**
     * Overall promotion statistics
     */
    public function getStatistics()
    {
        $stats = [
            'total' => Promotion::count(),
            'active' => Promotion::where('status', 'active')->count(),
            'inactive' => Promotion::where('status', 'inactive')->count(),
            'expired' => Promotion::where('end_date', '<', now())->count(),
            'total_used' => (int) Promotion::sum('used_count'),
            'by_discount_type' => Promotion::selectRaw('discount_type, COUNT(*) as count')
                ->groupBy('discount_type')
                ->get()
                ->pluck('count', 'discount_type'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Usage report of a specific promotion (which orders used it)
     */
    public function getUsageReport($id)
    {
        $promotion = Promotion::findOrFail($id);

        $orders = $promotion->orders()
            ->with('user:id,full_name,email')
            ->select('id', 'user_id', 'order_code', 'total_amount', 'discount_amount', 'order_status', 'created_at')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'promotion' => $promotion,
                'total_orders' => $orders->count(),
                'total_discount_given' => (float) $orders->sum('discount_amount'),
                'orders' => $orders,
            ],
        ]);
    }
}