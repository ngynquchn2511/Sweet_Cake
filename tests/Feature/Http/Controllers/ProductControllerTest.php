<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\ProductController
 */
final class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function index_displays_view(): void
    {
        $products = Product::factory()->count(3)->create(['status' => 'active']);

        $response = $this->get(route('products.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Customer/Products/Index'));
    }
}
