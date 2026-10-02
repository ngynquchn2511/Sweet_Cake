<?php

namespace App\Http\Controllers;

use App\Models\ShippingAddress;
use Illuminate\Http\Request;

class ShippingAddressController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $addresses = \App\Models\ShippingAddress::where('user_id', auth()->id())->get();
        
        return \Inertia\Inertia::render('ShippingAddress/Index', [
            'addresses' => $addresses
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Không truyền address -> Chế độ Thêm
        return \Inertia\Inertia::render('ShippingAddress/ShippingAddressForm');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ShippingAddress::validationRules());
        $data['is_default'] = (bool) ($data['is_default'] ?? false);

        $userId = auth()->id();

        // Nếu địa chỉ này là mặc định, reset các địa chỉ cũ của User này
        if ($request->is_default) {
            \App\Models\ShippingAddress::where('user_id', $userId)
                ->update(['is_default' => false]);
        }

        ShippingAddress::create(array_merge($data, ['user_id' => $userId]));

        return back()->with('success', 'Đã thêm địa chỉ giao hàng!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $address = ShippingAddress::findOrFail($id);

        if ($address->user_id !== auth()->id()) {
            abort(403);
        }

        // Không có trang xem riêng: chi tiết địa chỉ được hiển thị ngay trong form chỉnh sửa
        return redirect()->route('shipping-addresses.edit', $address->id);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $address = \App\Models\ShippingAddress::findOrFail($id);

        // Đảm bảo user chỉ được sửa địa chỉ của chính họ
        if ($address->user_id !== auth()->id()) {
            abort(403);
        }

        return \Inertia\Inertia::render('ShippingAddress/ShippingAddressForm', [
            'address' => $address
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $address = \App\Models\ShippingAddress::findOrFail($id);

        if ($address->user_id !== auth()->id()) {
            abort(403);
        }

        $data = $request->validate(ShippingAddress::validationRules());
        $data['is_default'] = (bool) ($data['is_default'] ?? false);

        // Cập nhật địa chỉ này TRƯỚC, sau đó mới bỏ mặc định các địa chỉ khác (nếu cần).
        // Làm ngược lại sẽ khiến Eloquent không nhận diện is_default là "dirty" và bỏ qua nó khi save().
        $address->update($data);

        if ($request->is_default) {
            \App\Models\ShippingAddress::where('user_id', auth()->id())
                ->where('id', '!=', $address->id)
                ->update(['is_default' => false]);
        }

        return redirect()->route('shipping-addresses.index')->with('success', 'Cập nhật địa chỉ thành công!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $address = \App\Models\ShippingAddress::findOrFail($id);

        // Đảm bảo user chỉ được xóa địa chỉ của chính họ
        if ($address->user_id !== auth()->id()) {
            abort(403);
        }

        if ($address->is_default) {
            return redirect()->route('shipping-addresses.index')
                ->with('error', 'Không thể xóa địa chỉ mặc định. Vui lòng đặt địa chỉ khác làm mặc định trước.');
        }

        $address->delete();

        return redirect()->route('shipping-addresses.index')->with('success', 'Xóa địa chỉ thành công!');
    }
}
