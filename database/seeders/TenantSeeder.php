<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Tag;
use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        // Create 3 demo tenants
        $tenants = Tenant::factory()->count(3)->create();

        foreach ($tenants as $tenant) {
            TenantContext::set($tenant);
            
            $this->command->info("Seeding tenant: {$tenant->name}");
            
            // Create employees
            $owner = Employee::factory()->create([
                'tenant_id' => $tenant->id,
                'name' => 'Owner User',
                'email' => "owner@{$tenant->slug}.com",
                'password' => Hash::make('password'),
            ]);
            $owner->assignRole('owner');
            
            $admin = Employee::factory()->create([
                'tenant_id' => $tenant->id,
                'name' => 'Admin User',
                'email' => "admin@{$tenant->slug}.com",
                'password' => Hash::make('password'),
            ]);
            $admin->assignRole('admin');
            
            Employee::factory()->count(3)->create([
                'tenant_id' => $tenant->id,
            ])->each(function ($employee) {
                $employee->assignRole('staff');
            });
            
            // Create customers
            $customers = Customer::factory()->count(20)->create([
                'tenant_id' => $tenant->id,
            ]);
            
            // Create categories (hierarchical)
            $rootCategories = Category::factory()->count(5)->create([
                'tenant_id' => $tenant->id,
                'parent_id' => null,
            ]);
            
            foreach ($rootCategories as $rootCategory) {
                Category::factory()->count(rand(2, 4))->create([
                    'tenant_id' => $tenant->id,
                    'parent_id' => $rootCategory->id,
                ]);
            }
            
            // Create tags
            $tags = Tag::factory()->count(15)->create([
                'tenant_id' => $tenant->id,
            ]);
            
            // Create products with variants
            $products = Product::factory()
                ->count(50)
                ->create([
                    'tenant_id' => $tenant->id,
                ]);
            
            foreach ($products as $product) {
                // 70% of products are published
                if (rand(1, 100) <= 70) {
                    $product->update([
                        'status' => 'published',
                        'published_at' => now()->subDays(rand(1, 90)),
                    ]);
                }
                
                // Attach categories (1-3 per product)
                $categories = Category::where('tenant_id', $tenant->id)
                    ->whereNotNull('parent_id')
                    ->inRandomOrder()
                    ->limit(rand(1, 3))
                    ->pluck('id');
                $product->categories()->attach($categories);
                
                // Attach tags (0-5 per product)
                $productTags = $tags->random(rand(0, 5))->pluck('id');
                $product->tags()->attach($productTags);
                
                // Create variants (1-4 per product)
                $variants = ProductVariant::factory()
                    ->count(rand(1, 4))
                    ->create([
                        'product_id' => $product->id,
                        'tenant_id' => $tenant->id,
                    ]);
                
                // Create inventory for each variant
                foreach ($variants as $variant) {
                    InventoryItem::factory()->create([
                        'product_variant_id' => $variant->id,
                        'tenant_id' => $tenant->id,
                    ]);
                }
            }
            
            // Create orders (mix of statuses)
            $orderStatuses = ['pending', 'paid', 'processing', 'fulfilled', 'completed', 'cancelled'];
            
            foreach ($customers->random(15) as $customer) {
                $orderCount = rand(1, 5);
                
                for ($i = 0; $i < $orderCount; $i++) {
                    $status = $orderStatuses[array_rand($orderStatuses)];
                    
                    $order = Order::factory()->create([
                        'tenant_id' => $tenant->id,
                        'customer_id' => $customer->id,
                        'status' => $status,
                        'placed_at' => in_array($status, ['paid', 'processing', 'fulfilled', 'completed']) 
                            ? now()->subDays(rand(1, 60)) 
                            : null,
                    ]);
                    
                    // Create order lines (1-5 items per order)
                    $lineCount = rand(1, 5);
                    $variants = ProductVariant::where('tenant_id', $tenant->id)
                        ->inRandomOrder()
                        ->limit($lineCount)
                        ->get();
                    
                    $subtotal = 0;
                    foreach ($variants as $variant) {
                        $quantity = rand(1, 3);
                        $price = $variant->price_cents;
                        
                        $order->lines()->create([
                            'product_variant_id' => $variant->id,
                            'sku' => $variant->sku,
                            'title' => $variant->product->title,
                            'quantity' => $quantity,
                            'price_cents' => $price,
                            'tax_cents' => (int) ($price * $quantity * 0.08),
                        ]);
                        
                        $subtotal += $price * $quantity;
                    }
                    
                    // Update order totals
                    $tax = (int) ($subtotal * 0.08);
                    $shipping = rand(500, 2000);
                    $order->update([
                        'subtotal_cents' => $subtotal,
                        'tax_cents' => $tax,
                        'shipping_cents' => $shipping,
                        'total_cents' => $subtotal + $tax + $shipping,
                    ]);
                    
                    // Create payment for paid orders
                    if (in_array($status, ['paid', 'processing', 'fulfilled', 'completed'])) {
                        $order->payments()->create([
                            'tenant_id' => $tenant->id,
                            'provider' => 'stripe',
                            'status' => 'captured',
                            'amount_cents' => $order->total_cents,
                            'currency' => 'USD',
                            'provider_transaction_id' => 'txn_' . uniqid(),
                        ]);
                        
                        // Create shipment for fulfilled/completed orders
                        if (in_array($status, ['fulfilled', 'completed'])) {
                            $order->shipments()->create([
                                'tenant_id' => $tenant->id,
                                'carrier' => 'ups',
                                'tracking_number' => 'UPS' . strtoupper(uniqid()),
                                'status' => $status === 'completed' ? 'delivered' : 'in_transit',
                                'delivered_at' => $status === 'completed' ? now()->subDays(rand(1, 10)) : null,
                            ]);
                        }
                    }
                }
            }
            
            // Create coupons
            Coupon::factory()->count(10)->create([
                'tenant_id' => $tenant->id,
            ]);
            
            // Create some expired coupons
            Coupon::factory()->count(3)->expired()->create([
                'tenant_id' => $tenant->id,
            ]);
            
            $this->command->info("✓ Tenant {$tenant->name} seeded successfully");
            
            TenantContext::clear();
        }
        
        $this->command->info("\n=================================");
        $this->command->info("Seeding completed successfully!");
        $this->command->info("=================================\n");
        $this->command->info("Demo Credentials:");
        
        foreach ($tenants as $tenant) {
            $this->command->info("\nTenant: {$tenant->name} ({$tenant->slug})");
            $this->command->info("  Owner: owner@{$tenant->slug}.com / password");
            $this->command->info("  Admin: admin@{$tenant->slug}.com / password");
            $this->command->info("  Tenant ID: {$tenant->id}");
        }
        
        $this->command->info("\n");
    }
}

