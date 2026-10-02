<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\ShippingAddress;
use Illuminate\Database\Seeder;

class ShippingAddressSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tạo user test
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'full_name' => 'Nguyễn Văn Test',
            'phone' => '0901234567',
        ]);

        // Tạo 3 địa chỉ test cho user
        ShippingAddress::factory()->create([
            'user_id' => $user->id,
            'receiver_name' => 'Nguyễn Văn A',
            'phone' => '0912345678',
            'province' => 'Hà Nội',
            'district' => 'Ba Đình',
            'ward' => 'Phú Thượng',
            'address' => '123 Đường Láng, Tây Hồ, Hà Nội',
            'note' => 'Nhà ở gồ, vào từ cổng bên trái',
            'is_default' => true,
        ]);

        ShippingAddress::factory()->create([
            'user_id' => $user->id,
            'receiver_name' => 'Trần Thị B',
            'phone' => '0987654321',
            'province' => 'TP. Hồ Chí Minh',
            'district' => 'Quận 1',
            'ward' => 'Bến Nghé',
            'address' => '456 Nguyễn Huệ, Quận 1, TPHCM',
            'note' => 'Tòa nhà cao ốc, lầu 10',
            'is_default' => false,
        ]);

        ShippingAddress::factory()->create([
            'user_id' => $user->id,
            'receiver_name' => 'Phạm Văn C',
            'phone' => '0909999999',
            'province' => 'Đà Nẵng',
            'district' => 'Hải Châu',
            'ward' => 'Thanh Khê',
            'address' => '789 Lê Duẩn, Đà Nẵng',
            'note' => 'Nhà liền kề, cột điện số 45',
            'is_default' => false,
        ]);

        // Tạo user khác để test authorization
        $user2 = User::factory()->create([
            'email' => 'user2@example.com',
            'password' => bcrypt('password123'),
            'full_name' => 'Lê Thị D',
        ]);

        // Tạo địa chỉ cho user2
        ShippingAddress::factory()->create([
            'user_id' => $user2->id,
            'receiver_name' => 'Lê Thị D',
            'phone' => '0918888888',
            'province' => 'Cần Thơ',
            'district' => 'Ninh Kiều',
            'ward' => 'Cái Khế',
            'address' => '101 Nguyễn Văn Linh, Cần Thơ',
            'is_default' => true,
        ]);

        echo "✅ ShippingAddress Seeder hoàn tất!\n\n";
        echo "📌 Tài khoản Test:\n";
        echo "   Email: test@example.com\n";
        echo "   Password: password123\n";
        echo "   Địa chỉ có sẵn: 3\n\n";
        echo "📌 Tài khoản Test 2:\n";
        echo "   Email: user2@example.com\n";
        echo "   Password: password123\n";
    }
}
