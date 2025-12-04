<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Product;
use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('passport:install', ['--force' => true]);
        $this->artisan('db:seed', ['--class' => 'PermissionSeeder']);
    }

    public function test_employee_can_only_access_own_tenant_products(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        TenantContext::set($tenant1);
        $employee1 = Employee::factory()->create(['tenant_id' => $tenant1->id]);
        $product1 = Product::factory()->create(['tenant_id' => $tenant1->id]);

        TenantContext::set($tenant2);
        $product2 = Product::factory()->create(['tenant_id' => $tenant2->id]);

        TenantContext::clear();

        Passport::actingAs($employee1);

        $response = $this->withHeader('X-Tenant-Id', $tenant1->id)
            ->getJson("/api/v1/products/{$product1->id}");

        $response->assertStatus(200);

        $response = $this->withHeader('X-Tenant-Id', $tenant1->id)
            ->getJson("/api/v1/products/{$product2->id}");

        $response->assertStatus(404);
    }

    public function test_products_are_automatically_scoped_to_tenant(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        TenantContext::set($tenant1);
        Product::factory()->count(5)->create(['tenant_id' => $tenant1->id]);

        TenantContext::set($tenant2);
        Product::factory()->count(3)->create(['tenant_id' => $tenant2->id]);

        TenantContext::clear();

        TenantContext::set($tenant1);
        $this->assertCount(5, Product::all());

        TenantContext::set($tenant2);
        $this->assertCount(3, Product::all());
    }

    public function test_middleware_blocks_request_without_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $employee = Employee::factory()->create(['tenant_id' => $tenant->id]);

        Passport::actingAs($employee);

        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(400)
            ->assertJson([
                'error' => [
                    'code' => 'TENANT_REQUIRED',
                ],
            ]);
    }

    public function test_middleware_blocks_suspended_tenant(): void
    {
        $tenant = Tenant::factory()->suspended()->create();
        $employee = Employee::factory()->create(['tenant_id' => $tenant->id]);

        Passport::actingAs($employee);

        $response = $this->withHeader('X-Tenant-Id', $tenant->id)
            ->getJson('/api/v1/products');

        $response->assertStatus(403)
            ->assertJson([
                'error' => [
                    'code' => 'TENANT_SUSPENDED',
                ],
            ]);
    }
}

