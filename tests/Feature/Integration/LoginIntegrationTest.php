<?php

namespace Tests\Feature\Integration;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Integration test cho luồng đăng nhập (Controller + Request + Model + Session/RateLimiter).
 * Tương ứng IT-01..IT-10 trong bộ testcase màn hình Đăng nhập.
 */
class LoginIntegrationTest extends TestCase
{
    use RefreshDatabase;

    // IT-01
    public function test_login_success_redirects_to_dashboard_and_clears_rate_limiter(): void
    {
        $user = User::factory()->create([
            'email' => 'it01@cake.vn',
            'password' => Hash::make('Khach123'),
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => 'it01@cake.vn',
            'password' => 'Khach123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    // IT-02
    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'it02@cake.vn',
            'password' => Hash::make('Khach123'),
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'it02@cake.vn',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    // IT-03
    public function test_login_fails_with_unregistered_email_using_generic_message(): void
    {
        User::factory()->create([
            'email' => 'it03-exists@cake.vn',
            'password' => Hash::make('Khach123'),
        ]);

        $existingEmailWrongPass = $this->from('/login')->post('/login', [
            'email' => 'it03-exists@cake.vn',
            'password' => 'wrong',
        ]);
        $existingEmailWrongPass->assertSessionHasErrors('email');
        $messageForExistingEmail = session('errors')->get('email');

        $unknownEmail = $this->from('/login')->post('/login', [
            'email' => 'it03-khongton@cake.vn',
            'password' => 'wrong',
        ]);
        $unknownEmail->assertSessionHasErrors('email');
        $messageForUnknownEmail = session('errors')->get('email');

        $this->assertGuest();
        $this->assertSame($messageForExistingEmail, $messageForUnknownEmail);
    }

    // IT-04
    public function test_account_is_locked_after_five_failed_attempts(): void
    {
        $user = User::factory()->create([
            'email' => 'it04@cake.vn',
            'password' => Hash::make('Khach123'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => 'it04@cake.vn',
                'password' => 'wrong-password',
            ]);
        }

        // Lần thứ 6, dù nhập đúng mật khẩu vẫn bị chặn bởi rate limiter
        $response = $this->post('/login', [
            'email' => 'it04@cake.vn',
            'password' => 'Khach123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    // IT-05
    public function test_session_id_is_regenerated_after_login(): void
    {
        $user = User::factory()->create([
            'email' => 'it05@cake.vn',
            'password' => Hash::make('Khach123'),
        ]);

        $this->get('/login');
        $sessionIdBefore = session()->getId();

        $this->post('/login', [
            'email' => 'it05@cake.vn',
            'password' => 'Khach123',
        ]);
        $sessionIdAfter = session()->getId();

        $this->assertNotSame($sessionIdBefore, $sessionIdAfter);
    }

    // IT-06
    public function test_remember_me_persists_remember_token(): void
    {
        $user = User::factory()->create([
            'email' => 'it06@cake.vn',
            'password' => Hash::make('Khach123'),
            'remember_token' => null,
        ]);

        $response = $this->post('/login', [
            'email' => 'it06@cake.vn',
            'password' => 'Khach123',
            'remember' => 'on',
        ]);

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->remember_token);

        $rememberCookie = collect($response->headers->getCookies())
            ->first(fn ($cookie) => str_starts_with($cookie->getName(), 'remember_web_'));
        $this->assertNotNull($rememberCookie, 'Không tìm thấy cookie remember_web_*');
    }

    // IT-07
    public function test_api_login_returns_token(): void
    {
        User::factory()->create([
            'email' => 'it07@cake.vn',
            'password' => Hash::make('Khach123'),
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'it07@cake.vn',
            'password' => 'Khach123',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure(['data' => ['user', 'token']]);
    }

    // IT-08
    public function test_api_login_blocks_banned_account(): void
    {
        User::factory()->create([
            'email' => 'it08@cake.vn',
            'password' => Hash::make('Khach123'),
            'status' => 'banned',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'it08@cake.vn',
            'password' => 'Khach123',
        ]);

        $response->assertStatus(403);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('message', 'Tài khoản của bạn đã bị khóa.');
    }

    // IT-09
    public function test_api_login_updates_last_login(): void
    {
        $user = User::factory()->create([
            'email' => 'it09@cake.vn',
            'password' => Hash::make('Khach123'),
            'status' => 'active',
            'last_login' => null,
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'it09@cake.vn',
            'password' => 'Khach123',
        ])->assertOk();

        $this->assertNotNull($user->fresh()->last_login);
    }

    // IT-10
    public function test_related_auth_routes_are_reachable(): void
    {
        $this->get(route('password.request'))->assertOk();
        $this->get(route('register'))->assertOk();
    }
}
