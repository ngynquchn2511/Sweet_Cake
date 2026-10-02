<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    /**
     * Mật khẩu mặc định dùng chung cho mọi user được factory tạo ra,
     * để test có thể đăng nhập bằng chuỗi 'password' theo quy ước chuẩn của Laravel.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'username' => fake()->unique()->userName(),
            'full_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            // Số điện thoại VN 10 chữ số - luôn nằm trong giới hạn max:15 của các rule validate
            'phone' => fake()->unique()->numerify('09########'),
            'password' => static::$password ??= Hash::make('password'),
            'avatar' => fake()->word(),
            // Mặc định là khách hàng đang hoạt động; test cần admin/bị khoá dùng state admin()/banned()
            'role' => 'customer',
            'status' => 'active',
            'email_verified_at' => fake()->dateTime(),
            'reset_token' => fake()->regexify('[A-Za-z0-9]{100}'),
            'last_login' => fake()->dateTime(),
        ];
    }

    /**
     * Tài khoản chưa xác minh email.
     */
    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => 'admin']);
    }

    public function banned(): static
    {
        return $this->state(fn () => ['status' => 'banned']);
    }
}
