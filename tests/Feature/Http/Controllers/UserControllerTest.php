<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Admin\UserController
 *
 * Bản sinh tự động (Blueprint) cũ gọi route "users.index" không tồn tại; nay kiểm tra đúng trang quản trị thật.
 */
final class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function index_displays_view(): void
    {
        User::factory()->count(3)->create();
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Admin/Users/Index')->has('users.data'));
    }

    #[Test]
    public function index_is_forbidden_for_customers(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->get(route('admin.users.index'))->assertRedirect('/');
    }
}
