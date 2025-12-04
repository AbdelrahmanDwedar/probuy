<?php

namespace Tests\Unit;

use App\Models\Coupon;
use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponTest extends TestCase
{
    use RefreshDatabase;

    public function test_coupon_is_active_within_date_range(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant);

        $coupon = Coupon::factory()->create([
            'starts_at' => now()->subDays(7),
            'ends_at' => now()->addDays(7),
            'usage_limit' => 100,
            'usage_count' => 10,
        ]);

        $this->assertTrue($coupon->isActive());
    }

    public function test_coupon_is_not_active_when_expired(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant);

        $coupon = Coupon::factory()->expired()->create();

        $this->assertFalse($coupon->isActive());
    }

    public function test_coupon_is_not_active_when_usage_limit_reached(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant);

        $coupon = Coupon::factory()->create([
            'usage_limit' => 100,
            'usage_count' => 100,
        ]);

        $this->assertFalse($coupon->isActive());
    }

    public function test_percentage_coupon_calculates_discount_correctly(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant);

        $coupon = Coupon::factory()->create([
            'type' => 'percentage',
            'value' => 2000, // 20%
        ]);

        $discount = $coupon->calculateDiscount(10000); // $100 order

        $this->assertEquals(2000, $discount); // $20 discount
    }

    public function test_fixed_coupon_calculates_discount_correctly(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant);

        $coupon = Coupon::factory()->create([
            'type' => 'fixed',
            'value' => 1500, // $15
        ]);

        $discount = $coupon->calculateDiscount(10000); // $100 order

        $this->assertEquals(1500, $discount); // $15 discount
    }

    public function test_coupon_cannot_be_applied_below_minimum_purchase(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant);

        $coupon = Coupon::factory()->create([
            'minimum_purchase_cents' => 5000,
        ]);

        $this->assertFalse($coupon->canBeAppliedTo(3000));
        $this->assertTrue($coupon->canBeAppliedTo(6000));
    }
}

