<?php

namespace Tests\Unit;

use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_can_be_reserved(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant);

        $variant = ProductVariant::factory()->create();
        $inventory = InventoryItem::factory()->create([
            'product_variant_id' => $variant->id,
            'stock_on_hand' => 100,
            'stock_reserved' => 0,
        ]);

        $reservation = $inventory->reserve(10);

        $this->assertEquals(10, $inventory->fresh()->stock_reserved);
        $this->assertEquals(90, $inventory->fresh()->available_stock);
        $this->assertNotNull($reservation->expires_at);
    }

    public function test_cannot_reserve_more_than_available_stock(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant);

        $variant = ProductVariant::factory()->create();
        $inventory = InventoryItem::factory()->create([
            'product_variant_id' => $variant->id,
            'stock_on_hand' => 10,
            'stock_reserved' => 0,
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Insufficient stock');

        $inventory->reserve(15);
    }

    public function test_stock_can_be_released(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant);

        $variant = ProductVariant::factory()->create();
        $inventory = InventoryItem::factory()->create([
            'product_variant_id' => $variant->id,
            'stock_on_hand' => 100,
            'stock_reserved' => 0,
        ]);

        $reservation = $inventory->reserve(10);
        $inventory->release($reservation);

        $this->assertEquals(0, $inventory->fresh()->stock_reserved);
        $this->assertNotNull($reservation->fresh()->released_at);
    }

    public function test_stock_adjustment_creates_movement(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant);

        $variant = ProductVariant::factory()->create();
        $inventory = InventoryItem::factory()->create([
            'product_variant_id' => $variant->id,
            'stock_on_hand' => 100,
        ]);

        $movement = $inventory->adjust(50, 'purchase');

        $this->assertEquals(150, $inventory->fresh()->stock_on_hand);
        $this->assertEquals(50, $movement->delta);
        $this->assertEquals('purchase', $movement->reason);
    }

    public function test_low_stock_detection(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant);

        $variant = ProductVariant::factory()->create();
        $inventory = InventoryItem::factory()->create([
            'product_variant_id' => $variant->id,
            'stock_on_hand' => 5,
            'low_stock_threshold' => 10,
        ]);

        $this->assertTrue($inventory->isLowStock());
    }
}

