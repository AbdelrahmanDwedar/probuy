<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Employee;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Tag;
use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('passport:install', ['--force' => true]);
        $this->artisan('db:seed', ['--class' => 'PermissionSeeder']);

        $this->tenant = Tenant::factory()->create();
        TenantContext::set($this->tenant);
        $this->employee = Employee::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->employee->assignRole('admin');

        Passport::actingAs($this->employee);
    }

    public function test_can_list_products(): void
    {
        Product::factory()->count(5)->create(['tenant_id' => $this->tenant->id]);

        $response = $this->withHeader('X-Tenant-Id', $this->tenant->id)
            ->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'sku', 'title', 'status'],
                ],
                'meta',
            ]);
    }

    public function test_can_create_product(): void
    {
        $category = Category::factory()->create(['tenant_id' => $this->tenant->id]);
        $tag = Tag::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->withHeader('X-Tenant-Id', $this->tenant->id)
            ->postJson('/api/v1/products', [
                'sku' => 'TEST-001',
                'title' => 'Test Product',
                'description' => 'This is a test product',
                'categories' => [$category->id],
                'tags' => [$tag->id],
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'sku' => 'TEST-001',
                    'title' => 'Test Product',
                ],
            ]);

        $this->assertDatabaseHas('products', [
            'tenant_id' => $this->tenant->id,
            'sku' => 'TEST-001',
        ]);
    }

    public function test_cannot_publish_product_without_variants(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'draft',
        ]);

        $response = $this->withHeader('X-Tenant-Id', $this->tenant->id)
            ->postJson("/api/v1/products/{$product->id}/publish");

        $response->assertStatus(400)
            ->assertJson([
                'error' => [
                    'code' => 'BUSINESS_LOGIC_ERROR',
                ],
            ]);
    }

    public function test_can_publish_product_with_variants(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'draft',
        ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->withHeader('X-Tenant-Id', $this->tenant->id)
            ->postJson("/api/v1/products/{$product->id}/publish");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'status' => 'published',
                ],
            ]);
    }

    public function test_can_filter_products_by_status(): void
    {
        Product::factory()->count(3)->published()->create(['tenant_id' => $this->tenant->id]);
        Product::factory()->count(2)->create(['tenant_id' => $this->tenant->id, 'status' => 'draft']);

        $response = $this->withHeader('X-Tenant-Id', $this->tenant->id)
            ->getJson('/api/v1/products?status=published');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }
}

