<?php

namespace Tests\Feature\System;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * System test cho luồng đăng nhập — kiểm tra qua HTTP client đầu-cuối theo đúng quy ước
 * "System" đã dùng trong CustomerFlowSystemTest (không phải kiểm thử trình duyệt/Dusk).
 * Tương ứng các case hành vi (không phải hình ảnh) trong bộ testcase: ST-03..ST-11.
 * ST-01, ST-02, ST-12, ST-13 là kiểm tra trực quan/giao diện — không thể xác minh qua HTTP test,
 * cần kiểm bằng mắt/Dusk và không được đưa vào đây.
 */
class LoginSystemTest extends TestCase
{
    use RefreshDatabase;

    // ST-03
    public function test_successful_login_lands_on_dashboard(): void
    {
        User::factory()->create([
            'email' => 'st03@cake.vn',
            'password' => Hash::make('Khach123'),
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => 'st03@cake.vn',
            'password' => 'Khach123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    // ST-04
    public function test_wrong_password_shows_error_and_stays_guest(): void
    {
        User::factory()->create([
            'email' => 'st04@cake.vn',
            'password' => Hash::make('Khach123'),
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'st04@cake.vn',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
        $response->assertRedirect('/login');
    }

    // ST-05
    public function test_empty_fields_are_rejected_before_reaching_auth(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => '',
            'password' => '',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['email', 'password']);
    }

    // ST-06
    public function test_invalid_email_format_is_rejected(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'vanA.gmail',
            'password' => 'Khach123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    // ST-07
    public function test_account_is_blocked_after_five_wrong_attempts_in_a_row(): void
    {
        User::factory()->create([
            'email' => 'st07@cake.vn',
            'password' => Hash::make('Khach123'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => 'st07@cake.vn',
                'password' => 'wrong',
            ]);
        }

        $response = $this->post('/login', [
            'email' => 'st07@cake.vn',
            'password' => 'Khach123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    // ST-08
    public function test_remember_me_keeps_user_authenticated_across_new_session(): void
    {
        $user = User::factory()->create([
            'email' => 'st08@cake.vn',
            'password' => Hash::make('Khach123'),
        ]);

        $loginResponse = $this->post('/login', [
            'email' => 'st08@cake.vn',
            'password' => 'Khach123',
            'remember' => 'on',
        ]);

        $rememberCookie = collect($loginResponse->headers->getCookies())
            ->first(fn ($cookie) => str_starts_with($cookie->getName(), 'remember_web_'));
        $this->assertNotNull($rememberCookie, 'Không tìm thấy cookie remember_web_*');

        // Mô phỏng "đóng và mở lại trình duyệt": phiên mới, chỉ còn cookie remember.
        $this->flushSession();
        $response = $this->withUnencryptedCookie($rememberCookie->getName(), $rememberCookie->getValue())
            ->get('/dashboard');

        $response->assertOk();
        $this->assertAuthenticatedAs($user);
    }

    // ST-09
    public function test_forgot_password_page_is_reachable(): void
    {
        $this->get(route('password.request'))->assertOk();
    }

    // ST-10
    public function test_register_page_is_reachable(): void
    {
        $this->get(route('register'))->assertOk();
    }

    // ST-11
    public function test_banned_account_cannot_use_api_login(): void
    {
        User::factory()->create([
            'email' => 'st11@cake.vn',
            'password' => Hash::make('Khach123'),
            'status' => 'banned',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'st11@cake.vn',
            'password' => 'Khach123',
        ]);

        $response->assertStatus(403);
        $response->assertJsonPath('message', 'Tài khoản của bạn đã bị khóa.');
    }
}
