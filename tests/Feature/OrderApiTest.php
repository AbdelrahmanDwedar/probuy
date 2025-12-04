<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class OrderApiTest extends TestCase
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

    public function test_can_create_order(): void
    {
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
        $variant = ProductVariant::factory()->create(['tenant_id' => $this->tenant->id]);
        InventoryItem::factory()->create([
            'product_variant_id' => $variant->id,
            'tenant_id' => $this->tenant->id,
            'stock_on_hand' => 100,
        ]);

        $response = $this->withHeader('X-Tenant-Id', $this->tenant->id)
            ->postJson('/api/v1/orders', [
                'customer_id' => $customer->id,
                'lines' => [
                    [
                        'variant_id' => $variant->id,
                        'quantity' => 2,
                    ],
                ],
                'billing_address' => [
                    'line1' => '123 Main St',
                    'city' => 'New York',
                    'state' => 'NY',
                    'zip' => '10001',
                    'country' => 'US',
                ],
                'shipping_address' => [
                    'line1' => '123 Main St',
                    'city' => 'New York',
                    'state' => 'NY',
                    'zip' => '10001',
                    'country' => 'US',
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'order_number',
                    'total_cents',
                    'status',
                    'lines',
                ],
            ]);

        $this->assertDatabaseHas('orders', [
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customer->id,
        ]);
    }

    public function test_can_list_orders(): void
    {
        Order::factory()->count(5)->create(['tenant_id' => $this->tenant->id]);

        $response = $this->withHeader('X-Tenant-Id', $this->tenant->id)
            ->getJson('/api/v1/orders');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    public function test_can_cancel_pending_order(): void
    {
        $order = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'pending',
        ]);

        $response = $this->withHeader('X-Tenant-Id', $this->tenant->id)
            ->postJson("/api/v1/orders/{$order->id}/cancel");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'status' => 'cancelled',
                ],
            ]);
    }
}

