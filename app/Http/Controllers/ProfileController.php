<?php
// app/Http/Controllers/ProfileController.php
// SMART CONTROLLER - Xử lý cả Web (Inertia) và API (JSON)

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\ShippingAddress;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    // ========================================
    // WEB METHODS (Inertia.js)
    // ========================================

    /**
     * Display the user's profile form (WEB)
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }
    public function editPassword(): Response
    {
        return Inertia::render('Profile/ChangePassword'); // Đảm bảo file React nằm đúng đường dẫn này
    }

    /**
     * Update the user's profile information (WEB)
     */
    public function updateWeb(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account (WEB)
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    // ========================================
    // API METHODS (JSON)
    // ========================================

    /**
     * Get user profile (API)
     */
    public function show()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->load(['shippingAddresses', 'orders']);

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * Update profile (API)
     */
    public function update(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $request->validate([
            'full_name' => 'required|string|max:100',
            'phone' => 'required|string|max:15|unique:users,phone,' . $user->id,
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = [
            'full_name' => $request->full_name,
            'phone' => $request->phone,
        ];

        // Upload avatar if provided
        if ($request->hasFile('avatar')) {
            if ($user->avatar && file_exists(public_path($user->avatar))) {
                unlink(public_path($user->avatar));
            }

            $avatar = $request->file('avatar');
            $avatarName = time() . '_' . uniqid() . '.' . $avatar->getClientOriginalExtension();
            $avatar->move(public_path('uploads/avatars'), $avatarName);
            $data['avatar'] = 'uploads/avatars/' . $avatarName;
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thông tin thành công',
            'data' => $user
        ]);
    }

    // ========================================
    // SHIPPING ADDRESS METHODS (API)
    // ========================================

    /**
     * Get shipping addresses (API)
     */
    public function getShippingAddresses()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $addresses = $user->shippingAddresses()->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $addresses
        ]);
    }

    /**
     * Store shipping address (API)
     */
    public function storeShippingAddress(Request $request)
    {
        $request->validate(ShippingAddress::validationRules());

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $address = $user->shippingAddresses()->create([
            'receiver_name' => $request->receiver_name,
            'phone' => $request->phone,
            'province' => $request->province,
            'district' => $request->district,
            'ward' => $request->ward,
            'address' => $request->address,
            'note' => $request->note,
            'is_default' => $request->is_default ?? false,
        ]);

        // Set as default if requested
        if ($request->is_default) {
            $address->setAsDefault();
        }

        return response()->json([
            'success' => true,
            'message' => 'Thêm địa chỉ thành công',
            'data' => $address
        ], 201);
    }

    /**
     * Update shipping address (API)
     */
    public function updateShippingAddress(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $address = $user->shippingAddresses()->findOrFail($id);

        $request->validate(ShippingAddress::validationRules());

        $address->update([
            'receiver_name' => $request->receiver_name,
            'phone' => $request->phone,
            'province' => $request->province,
            'district' => $request->district,
            'ward' => $request->ward,
            'address' => $request->address,
            'note' => $request->note,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật địa chỉ thành công',
            'data' => $address
        ]);
    }

    /**
     * Delete shipping address (API)
     */
    public function deleteShippingAddress($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $address = $user->shippingAddresses()->findOrFail($id);

        if ($address->is_default) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa địa chỉ mặc định'
            ], 422);
        }

        $address->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa địa chỉ thành công'
        ]);
    }

    /**
     * Set default address (API)
     */
    public function setDefaultAddress($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $address = $user->shippingAddresses()->findOrFail($id);
        $address->setAsDefault();

        return response()->json([
            'success' => true,
            'message' => 'Đã đặt làm địa chỉ mặc định',
            'data' => $address
        ]);
    }
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'], // Kiểm tra mk cũ có đúng ko
            'password' => ['required', Password::defaults(), 'confirmed'], // mk mới và confirm_password
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        // Thu hồi token API + session web khác để buộc đăng nhập lại ở các thiết bị khác
        $request->user()->revokeOtherSessions($request->session()->getId());

        return back()->with('message', 'Đã cập nhật mật khẩu thành công!');
    }
}
