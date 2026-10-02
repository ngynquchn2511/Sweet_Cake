<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ShippingAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingAddressEditTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $address;

    protected function setUp(): void
    {
        parent::setUp();

        // Tạo user test
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Tạo địa chỉ test
        $this->address = ShippingAddress::factory()->create([
            'user_id' => $this->user->id,
            'receiver_name' => 'Nguyễn Văn A',
            'phone' => '0123456789',
            'province' => 'Hà Nội',
            'district' => 'Ba Đình',
            'ward' => 'Phú Thượng',
            'address' => '123 Đường Láng',
            'is_default' => true,
        ]);
    }

    /**
     * Test: Lấy trang edit địa chỉ
     */
    public function test_user_can_view_edit_form()
    {
        $response = $this->actingAs($this->user)
            ->get(route('shipping-addresses.edit', $this->address->id));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('ShippingAddress/ShippingAddressForm')
            ->where('address.id', $this->address->id)
        );
    }

    /**
     * Test: Người dùng khác không thể edit địa chỉ của user này
     */
    public function test_user_cannot_edit_other_user_address()
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->get(route('shipping-addresses.edit', $this->address->id));

        $response->assertStatus(403);
    }

    /**
     * Test: Cập nhật địa chỉ thành công
     */
    public function test_user_can_update_address()
    {
        $updatedData = [
            'receiver_name' => 'Trần Thị B',
            'phone' => '0987654321',
            'province' => 'TP. Hồ Chí Minh',
            'district' => 'Quận 1',
            'ward' => 'Bến Nghé',
            'address' => '456 Nguyễn Huệ',
            'note' => 'Ghi chú mới',
            'is_default' => false,
        ];

        $response = $this->actingAs($this->user)
            ->put(route('shipping-addresses.update', $this->address->id), $updatedData);

        // Kiểm tra redirect
        $response->assertRedirect(route('shipping-addresses.index'));
        $response->assertSessionHas('success', 'Cập nhật địa chỉ thành công!');

        // Kiểm tra dữ liệu trong database
        $this->assertDatabaseHas('shipping_addresses', [
            'id' => $this->address->id,
            'user_id' => $this->user->id,
            'receiver_name' => 'Trần Thị B',
            'phone' => '0987654321',
            'province' => 'TP. Hồ Chí Minh',
            'district' => 'Quận 1',
            'ward' => 'Bến Nghé',
            'address' => '456 Nguyễn Huệ',
            'note' => 'Ghi chú mới',
            'is_default' => false,
        ]);
    }

    /**
     * Test: Validation error khi cập nhật với dữ liệu không hợp lệ
     */
    public function test_update_address_with_invalid_data()
    {
        $invalidData = [
            'receiver_name' => '', // Required
            'phone' => '', // Required
            'province' => 'Hà Nội',
            'district' => 'Ba Đình',
            'ward' => 'Phú Thượng',
            'address' => '123 Đường Láng',
            'is_default' => false,
        ];

        $response = $this->actingAs($this->user)
            ->put(route('shipping-addresses.update', $this->address->id), $invalidData);

        $response->assertSessionHasErrors(['receiver_name', 'phone']);
    }

    /**
     * Test: Đặt địa chỉ làm mặc định sẽ bỏ mặc định các địa chỉ khác
     */
    public function test_setting_as_default_removes_default_from_others()
    {
        // Tạo địa chỉ khác đã là mặc định
        $otherAddress = ShippingAddress::factory()->create([
            'user_id' => $this->user->id,
            'is_default' => true,
        ]);

        // Cập nhật địa chỉ đầu tiên thành mặc định
        $this->actingAs($this->user)
            ->put(route('shipping-addresses.update', $this->address->id), [
                'receiver_name' => 'Nguyễn Văn A',
                'phone' => '0123456789',
                'province' => 'Hà Nội',
                'district' => 'Ba Đình',
                'ward' => 'Phú Thượng',
                'address' => '123 Đường Láng',
                'is_default' => true,
            ]);

        // Kiểm tra địa chỉ đầu tiên là mặc định
        $this->assertTrue($this->address->fresh()->is_default);

        // Kiểm tra địa chỉ khác không còn là mặc định
        $this->assertFalse($otherAddress->fresh()->is_default);
    }

    /**
     * Test: Không thể cập nhật địa chỉ khác user
     */
    public function test_user_cannot_update_other_user_address()
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->put(route('shipping-addresses.update', $this->address->id), [
                'receiver_name' => 'Hacker',
                'phone' => '9999999999',
                'province' => 'Hacker Land',
                'district' => 'Hacker District',
                'ward' => 'Hacker Ward',
                'address' => 'Hacker Address',
                'is_default' => false,
            ]);

        $response->assertStatus(403);

        // Kiểm tra dữ liệu gốc không thay đổi
        $this->assertDatabaseHas('shipping_addresses', [
            'id' => $this->address->id,
            'receiver_name' => 'Nguyễn Văn A',
        ]);
    }
}
