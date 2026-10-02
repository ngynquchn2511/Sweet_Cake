<?php

namespace Tests\Unit\Auth;

use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Unit test cho LoginRequest — tương ứng UT-01..UT-06 trong bộ testcase màn hình Đăng nhập.
 */
class LoginRequestTest extends TestCase
{
    private function makeRequest(array $data, string $ip = '192.168.1.10'): LoginRequest
    {
        $request = LoginRequest::create('/login', 'POST', $data);
        $request->server->set('REMOTE_ADDR', $ip);

        return $request;
    }

    // UT-01
    public function test_email_is_required(): void
    {
        $request = $this->makeRequest(['email' => '', 'password' => '123456']);
        $validator = validator($request->all(), $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    // UT-02
    public function test_email_must_be_valid_format(): void
    {
        $request = $this->makeRequest(['email' => 'vanA-gmail.com', 'password' => '123456']);
        $validator = validator($request->all(), $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    // UT-03
    public function test_password_is_required(): void
    {
        $request = $this->makeRequest(['email' => 'khach@cake.vn', 'password' => '']);
        $validator = validator($request->all(), $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    // UT-04
    public function test_throttle_key_is_lowercased_and_transliterated(): void
    {
        $request = $this->makeRequest(['email' => 'VanA@Gmail.com', 'password' => 'x'], '192.168.1.10');

        $this->assertSame('vana@gmail.com|192.168.1.10', $request->throttleKey());
    }

    // UT-05
    public function test_not_rate_limited_when_attempts_under_five(): void
    {
        $request = $this->makeRequest(['email' => 'ut05@cake.vn', 'password' => 'x']);
        RateLimiter::clear($request->throttleKey());

        for ($i = 0; $i < 4; $i++) {
            RateLimiter::hit($request->throttleKey());
        }

        $request->ensureIsNotRateLimited();
        $this->assertTrue(true);
    }

    // UT-06
    public function test_rate_limited_when_attempts_reach_five(): void
    {
        $request = $this->makeRequest(['email' => 'ut06@cake.vn', 'password' => 'x']);
        RateLimiter::clear($request->throttleKey());

        for ($i = 0; $i < 5; $i++) {
            RateLimiter::hit($request->throttleKey());
        }

        $this->expectException(ValidationException::class);
        $request->ensureIsNotRateLimited();
    }
}
