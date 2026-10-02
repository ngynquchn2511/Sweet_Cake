<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Admin\OrderController
 *
 * Bản sinh tự động (Blueprint) cũ gọi route "orders.index" không tồn tại; nay kiểm tra đúng trang quản trị thật.
 */
final class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function index_displays_view(): void
    {
        Order::factory()->count(3)->create();
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.orders.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Admin/Orders/Index')->has('orders.data'));
    }

    #[Test]
    public function index_is_forbidden_for_customers(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->get(route('admin.orders.index'))->assertRedirect('/');
    }
}
