<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_calculates_totals_correctly(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant);

        $order = Order::factory()->create([
            'subtotal_cents' => 0,
            'tax_cents' => 0,
            'shipping_cents' => 1000,
            'total_cents' => 0,
        ]);

        $variant1 = ProductVariant::factory()->create(['price_cents' => 2000]);
        $variant2 = ProductVariant::factory()->create(['price_cents' => 3000]);

        $order->lines()->create([
            'product_variant_id' => $variant1->id,
            'sku' => $variant1->sku,
            'title' => 'Product 1',
            'quantity' => 2,
            'price_cents' => 2000,
            'tax_cents' => 320,
        ]);

        $order->lines()->create([
            'product_variant_id' => $variant2->id,
            'sku' => $variant2->sku,
            'title' => 'Product 2',
            'quantity' => 1,
            'price_cents' => 3000,
            'tax_cents' => 240,
        ]);

        $order->calculateTotals();

        $this->assertEquals(7000, $order->subtotal_cents); // 2000*2 + 3000*1
        $this->assertEquals(560, $order->tax_cents); // 320 + 240
        $this->assertEquals(8560, $order->total_cents); // 7000 + 560 + 1000
    }

    public function test_order_can_be_cancelled_when_pending(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant);

        $order = Order::factory()->create(['status' => 'pending']);

        $order->cancel();

        $this->assertEquals('cancelled', $order->status);
    }

    public function test_order_cannot_be_cancelled_when_completed(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant);

        $order = Order::factory()->completed()->create();

        $this->expectException(\DomainException::class);
        $order->cancel();
    }

    public function test_order_marked_as_paid_updates_status(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant);

        $order = Order::factory()->create(['status' => 'pending']);

        $order->markAsPaid();

        $this->assertEquals('paid', $order->status);
        $this->assertNotNull($order->placed_at);
    }
}

