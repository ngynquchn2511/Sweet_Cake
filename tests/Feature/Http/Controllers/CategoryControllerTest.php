<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Admin\CategoryController
 *
 * Bản sinh tự động (Blueprint) cũ gọi route "categories.index" không tồn tại; nay kiểm tra đúng trang quản trị thật.
 */
final class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function index_displays_view(): void
    {
        Category::factory()->count(3)->create();
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.categories.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Admin/Categories/Index')->has('categories.data'));
    }

    #[Test]
    public function index_is_forbidden_for_customers(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->get(route('admin.categories.index'))->assertRedirect('/');
    }
}
