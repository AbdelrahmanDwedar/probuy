<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'PermissionSeeder']);
    }

    public function test_product_can_be_published_when_valid(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant);

        $product = Product::factory()->create(['status' => 'draft']);
        ProductVariant::factory()->create(['product_id' => $product->id]);

        $product->publish();

        $this->assertEquals('published', $product->status);
        $this->assertNotNull($product->published_at);
    }

    public function test_product_cannot_be_published_without_variants(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant);

        $product = Product::factory()->create(['status' => 'draft']);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Product must have at least one variant');

        $product->publish();
    }

    public function test_product_can_be_archived(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant);

        $product = Product::factory()->published()->create();

        $product->archive();

        $this->assertEquals('archived', $product->status);
    }

    public function test_product_belongs_to_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant);

        $product = Product::factory()->create();

        $this->assertEquals($tenant->id, $product->tenant_id);
        $this->assertInstanceOf(Tenant::class, $product->tenant);
    }
}

