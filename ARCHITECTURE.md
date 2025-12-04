# Architecture Documentation

## Project Overview

**ProBuy** is a multi-tenant, API-only e-commerce platform built with Domain-Driven Design (DDD) principles. It enables multiple businesses (tenants) to operate independent online stores from a single application instance while maintaining strict data isolation and security.

### Key Goals

- **Multi-tenancy**: Enable multiple independent stores with complete data isolation
- **Scalability**: Support high-volume transactions and concurrent operations
- **Maintainability**: Clean architecture with clear separation of concerns
- **Security**: Row-level tenant isolation, RBAC, and audit trails
- **Performance**: Redis caching, background job processing, and optimized queries

## Tech Stack

### Core Framework & Language
- **PHP**: 8.1+
- **Laravel**: Latest LTS (10.x/11.x)
- **PostgreSQL**: 14+ (primary database)
- **Redis**: 7+ (cache & queue)

### Key Packages
- **Laravel Passport**: OAuth2 authentication
- **Spatie MediaLibrary**: Media management with tenant separation
- **Spatie Laravel-Permission**: Role-based access control
- **Laravel Horizon**: Queue monitoring and management
- **Laravel Telescope**: Debugging and monitoring (dev/staging)
- **PHPUnit/Pest**: Testing framework

### Infrastructure
- **Docker**: Containerization for local development
- **Supervisor**: Process management for queue workers
- **GitHub Actions**: CI/CD pipeline

## Domain-Driven Design (DDD) Structure

### Bounded Contexts

Our system is organized into 11 bounded contexts, each representing a distinct business domain:

#### 1. **Tenancy Context**
- **Aggregate Root**: `Tenant`
- **Responsibilities**: 
  - Tenant registration and onboarding
  - Domain/subdomain management
  - Tenant settings and configuration
  - Storage and billing metadata
- **Key Operations**: Create tenant, update settings, suspend/activate

#### 2. **Catalog Context**
- **Aggregate Roots**: `Product`, `ProductVariant`
- **Entities**: `Category`, `Tag`
- **Value Objects**: `Price`, `Dimension`, `Weight`, `SKU`
- **Responsibilities**:
  - Product lifecycle management (draft → published → archived)
  - Variant management (size, color, etc.)
  - Category hierarchies
  - Product tagging and search
- **Key Operations**: Create product, publish, add variants, categorize

#### 3. **Inventory Context**
- **Aggregate Root**: `InventoryItem`
- **Entities**: `StockMovement`, `StockReservation`
- **Responsibilities**:
  - Track stock levels per variant
  - Reserve/release stock for orders
  - Record inventory movements
  - Low stock alerts
- **Key Operations**: Adjust stock, reserve, release, transfer

#### 4. **Orders Context**
- **Aggregate Root**: `Order`
- **Entities**: `OrderLine`, `OrderStatusHistory`
- **Value Objects**: `Money`, `Address`
- **Responsibilities**:
  - Order creation and management
  - Order state machine (pending → paid → fulfilled → completed)
  - Order calculations (subtotal, tax, shipping, discounts)
- **Key Operations**: Create order, cancel, refund, update status

#### 5. **Payments Context**
- **Aggregate Root**: `Payment`
- **Responsibilities**:
  - Payment processing integration
  - Payment status tracking
  - Refund management
  - Webhook handling (idempotent)
- **Key Operations**: Process payment, verify, refund

#### 6. **Fulfillment Context**
- **Aggregate Root**: `Shipment`
- **Responsibilities**:
  - Shipment creation and tracking
  - Carrier integration
  - Shipping label generation
  - Delivery status updates
- **Key Operations**: Create shipment, generate label, track, deliver

#### 7. **Employees Context**
- **Aggregate Root**: `Employee`
- **Entities**: `Role`, `Permission` (via Spatie)
- **Responsibilities**:
  - Employee management
  - Authentication and authorization
  - Activity logging
  - Team management
- **Key Operations**: Create employee, assign roles, authenticate

#### 8. **Customers Context**
- **Aggregate Root**: `Customer`
- **Value Objects**: `CustomerAddress`
- **Responsibilities**:
  - Customer profile management
  - Address book
  - Guest checkout support
  - Customer segmentation
- **Key Operations**: Register, update profile, manage addresses

#### 9. **Media Context**
- **Aggregate Root**: `Media` (Spatie)
- **Responsibilities**:
  - File upload and storage
  - Image optimization and conversions
  - Tenant-isolated storage
  - Media cleanup
- **Key Operations**: Upload, attach, convert, delete

#### 10. **Notifications Context**
- **Aggregate Root**: `Notification`
- **Responsibilities**:
  - Email, SMS, push notifications
  - Template management
  - Delivery tracking
  - Notification preferences
- **Key Operations**: Send, queue, track, retry

#### 11. **Reporting Context**
- **Read Models**: Sales reports, inventory reports, customer analytics
- **Responsibilities**:
  - Generate business intelligence reports
  - Cache aggregated data
  - Export functionality
  - Dashboard metrics
- **Key Operations**: Generate report, export, cache refresh

### Layered Architecture

```
┌─────────────────────────────────────────────────┐
│           Presentation Layer (API)              │
│  Controllers, Requests, Resources, Middleware   │
└────────────────┬────────────────────────────────┘
                 │
┌────────────────▼────────────────────────────────┐
│           Application Layer                     │
│  Command Handlers, Query Handlers, DTOs         │
│  Application Services, Use Cases                │
└────────────────┬────────────────────────────────┘
                 │
┌────────────────▼────────────────────────────────┐
│           Domain Layer                          │
│  Entities, Value Objects, Domain Events         │
│  Domain Services, Repository Interfaces         │
└────────────────┬────────────────────────────────┘
                 │
┌────────────────▼────────────────────────────────┐
│         Infrastructure Layer                    │
│  Eloquent Models, Repository Implementations    │
│  External Services, Cache, Queue                │
└─────────────────────────────────────────────────┘
```

## Multi-Tenant Strategy

### Tenant Identification

**Method**: Row-level tenancy in a single database with `tenant_id` on all domain tables.

**API Tenant Resolution**:
1. Client sends `X-Tenant-Id` header (optional)
2. OR tenant derived from OAuth token (preferred)
3. `TenantMiddleware` resolves and sets `TenantContext::set($tenant)`
4. All subsequent queries automatically scoped to tenant

**Why Single Database?**
- Simpler infrastructure management
- Cost-effective for moderate scale
- Easier cross-tenant reporting (admin)
- Atomic backups
- Trade-off: requires strict query scoping

**Future Migration Path**: Design allows migration to database-per-tenant if needed.

### Tenant Isolation Mechanisms

#### 1. Database Level
```sql
-- All domain tables include:
tenant_id UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE

-- Composite indexes for performance:
CREATE INDEX idx_products_tenant ON products(tenant_id, id);
CREATE INDEX idx_orders_tenant ON orders(tenant_id, order_number);

-- Optional: Postgres Row-Level Security (future enhancement)
ALTER TABLE products ENABLE ROW LEVEL SECURITY;
CREATE POLICY tenant_isolation ON products
  USING (tenant_id = current_setting('app.current_tenant_id')::uuid);
```

#### 2. Application Level
```php
// TenantScoped trait applied to all domain models
trait TenantScoped
{
    protected static function bootTenantScoped()
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if ($tenant = TenantContext::get()) {
                $builder->where('tenant_id', $tenant->id);
            }
        });

        static::creating(function ($model) {
            if (!$model->tenant_id && $tenant = TenantContext::get()) {
                $model->tenant_id = $tenant->id;
            }
        });
    }
}
```

#### 3. Media Storage Isolation
```php
// Media stored under tenant-specific paths:
// Local: storage/app/tenants/{tenant_uuid}/media/...
// S3: s3://bucket/tenants/{tenant_uuid}/media/...

// Spatie Media config per tenant
public function registerMediaCollections(): void
{
    $this->addMediaCollection('images')
        ->useDisk(TenantMediaDisk::get()); // Returns tenant-specific disk
}
```

#### 4. Cache Key Isolation
```php
// All cache keys prefixed with tenant:
Cache::tags(['tenant:' . $tenantId])->put("product:{$id}", $data);

// Easy tenant-wide cache flush:
Cache::tags(['tenant:' . $tenantId])->flush();
```

#### 5. Queue Job Isolation
```php
// Jobs serialize tenant context:
class ProcessOrderJob implements ShouldQueue
{
    public function __construct(
        public string $tenantId,
        public string $orderId
    ) {}

    public function handle()
    {
        TenantContext::setById($this->tenantId);
        // ... process order
    }
}
```

### Tenant Security Checklist

✅ Every domain table has `tenant_id` NOT NULL  
✅ All Eloquent models use `TenantScoped` trait  
✅ Middleware enforces tenant context on every request  
✅ Repository methods validate tenant ownership  
✅ Media files stored in tenant-specific directories  
✅ Cache keys prefixed with tenant ID  
✅ Queue jobs include tenant context  
✅ Tests verify cross-tenant access is blocked  
✅ Foreign keys include tenant_id for referential integrity  

## Data Model

### Entity Relationship Overview

```
Tenant (root)
  ├── Employees (has many)
  │     └── Roles & Permissions (many-to-many)
  ├── Customers (has many)
  ├── Categories (has many, hierarchical)
  ├── Tags (has many)
  ├── Products (has many)
  │     ├── ProductVariants (has many)
  │     │     └── InventoryItems (has one)
  │     ├── Media (polymorphic many)
  │     └── Categories (many-to-many)
  ├── Orders (has many)
  │     ├── OrderLines (has many)
  │     ├── Payments (has many)
  │     └── Shipments (has one or many)
  ├── Coupons (has many)
  └── ActivityLogs (has many)
```

### Core Tables Schema

#### Tenants
```sql
CREATE TABLE tenants (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    settings JSONB DEFAULT '{}',
    status VARCHAR(50) DEFAULT 'active', -- active, suspended, cancelled
    trial_ends_at TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_tenants_slug ON tenants(slug);
CREATE INDEX idx_tenants_status ON tenants(status);
```

#### Employees
```sql
CREATE TABLE employees (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    email VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_active BOOLEAN DEFAULT true,
    meta JSONB DEFAULT '{}',
    email_verified_at TIMESTAMP,
    last_login_at TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE(tenant_id, email)
);

CREATE INDEX idx_employees_tenant ON employees(tenant_id, id);
CREATE INDEX idx_employees_email ON employees(email);
```

#### Customers
```sql
CREATE TABLE customers (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    email VARCHAR(255),
    name VARCHAR(255),
    phone VARCHAR(50),
    addresses JSONB DEFAULT '[]',
    meta JSONB DEFAULT '{}',
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE(tenant_id, email) WHERE email IS NOT NULL
);

CREATE INDEX idx_customers_tenant ON customers(tenant_id, id);
CREATE INDEX idx_customers_email ON customers(tenant_id, email);
```

#### Categories
```sql
CREATE TABLE categories (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    parent_id UUID REFERENCES categories(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    description TEXT,
    meta JSONB DEFAULT '{}',
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE(tenant_id, slug)
);

CREATE INDEX idx_categories_tenant ON categories(tenant_id, id);
CREATE INDEX idx_categories_parent ON categories(parent_id);
```

#### Tags
```sql
CREATE TABLE tags (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE(tenant_id, slug)
);

CREATE INDEX idx_tags_tenant ON tags(tenant_id);
```

#### Products
```sql
CREATE TABLE products (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    sku VARCHAR(255) NOT NULL,
    title VARCHAR(500) NOT NULL,
    description TEXT,
    status VARCHAR(50) DEFAULT 'draft', -- draft, published, archived
    metadata JSONB DEFAULT '{}',
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    published_at TIMESTAMP,
    UNIQUE(tenant_id, sku)
);

CREATE INDEX idx_products_tenant ON products(tenant_id, id);
CREATE INDEX idx_products_sku ON products(tenant_id, sku);
CREATE INDEX idx_products_status ON products(tenant_id, status);
CREATE INDEX idx_products_metadata ON products USING GIN(metadata);
```

#### Product Variants
```sql
CREATE TABLE product_variants (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    product_id UUID NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    tenant_id UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    sku VARCHAR(255) NOT NULL,
    price_cents INT NOT NULL DEFAULT 0,
    compare_at_price_cents INT,
    currency VARCHAR(3) DEFAULT 'USD',
    cost_cents INT,
    attributes JSONB DEFAULT '{}', -- {"size": "L", "color": "Red"}
    weight_grams INT,
    dimensions JSONB, -- {"length": 10, "width": 5, "height": 2}
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE(tenant_id, sku),
    FOREIGN KEY (tenant_id, product_id) REFERENCES products(tenant_id, id)
);

CREATE INDEX idx_variants_tenant ON product_variants(tenant_id, id);
CREATE INDEX idx_variants_product ON product_variants(product_id);
CREATE INDEX idx_variants_sku ON product_variants(tenant_id, sku);
```

#### Inventory Items
```sql
CREATE TABLE inventory_items (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    product_variant_id UUID NOT NULL REFERENCES product_variants(id) ON DELETE CASCADE,
    stock_on_hand INT NOT NULL DEFAULT 0,
    stock_reserved INT NOT NULL DEFAULT 0,
    low_stock_threshold INT DEFAULT 10,
    warehouse_location VARCHAR(255),
    meta JSONB DEFAULT '{}',
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE(tenant_id, product_variant_id)
);

CREATE INDEX idx_inventory_tenant ON inventory_items(tenant_id);
CREATE INDEX idx_inventory_variant ON inventory_items(product_variant_id);
```

#### Stock Reservations
```sql
CREATE TABLE stock_reservations (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    inventory_item_id UUID NOT NULL REFERENCES inventory_items(id) ON DELETE CASCADE,
    order_id UUID REFERENCES orders(id) ON DELETE CASCADE,
    quantity INT NOT NULL,
    reason VARCHAR(255),
    expires_at TIMESTAMP,
    released_at TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_reservations_tenant ON stock_reservations(tenant_id);
CREATE INDEX idx_reservations_inventory ON stock_reservations(inventory_item_id);
CREATE INDEX idx_reservations_expires ON stock_reservations(expires_at) WHERE released_at IS NULL;
```

#### Inventory Movements
```sql
CREATE TABLE inventory_movements (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    inventory_item_id UUID NOT NULL REFERENCES inventory_items(id) ON DELETE CASCADE,
    delta INT NOT NULL, -- positive = addition, negative = reduction
    reason VARCHAR(255) NOT NULL, -- purchase, sale, adjustment, return, damage
    reference_type VARCHAR(255), -- Order, StockTransfer, etc.
    reference_id UUID,
    actor_id UUID, -- employee who performed action
    meta JSONB DEFAULT '{}',
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_movements_tenant ON inventory_movements(tenant_id);
CREATE INDEX idx_movements_inventory ON inventory_movements(inventory_item_id);
CREATE INDEX idx_movements_created ON inventory_movements(created_at);
```

#### Orders
```sql
CREATE TABLE orders (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    order_number VARCHAR(255) NOT NULL,
    customer_id UUID REFERENCES customers(id) ON DELETE SET NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    subtotal_cents INT NOT NULL DEFAULT 0,
    tax_cents INT NOT NULL DEFAULT 0,
    shipping_cents INT NOT NULL DEFAULT 0,
    discount_cents INT NOT NULL DEFAULT 0,
    total_cents INT NOT NULL DEFAULT 0,
    status VARCHAR(50) NOT NULL DEFAULT 'pending', -- pending, paid, processing, fulfilled, completed, cancelled, refunded
    billing_address JSONB,
    shipping_address JSONB,
    customer_note TEXT,
    metadata JSONB DEFAULT '{}',
    placed_at TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE(tenant_id, order_number)
);

CREATE INDEX idx_orders_tenant ON orders(tenant_id, id);
CREATE INDEX idx_orders_number ON orders(tenant_id, order_number);
CREATE INDEX idx_orders_customer ON orders(customer_id);
CREATE INDEX idx_orders_status ON orders(tenant_id, status);
CREATE INDEX idx_orders_placed ON orders(placed_at);
```

#### Order Lines
```sql
CREATE TABLE order_lines (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_id UUID NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    product_variant_id UUID REFERENCES product_variants(id) ON DELETE SET NULL,
    sku VARCHAR(255) NOT NULL,
    title VARCHAR(500) NOT NULL,
    quantity INT NOT NULL,
    price_cents INT NOT NULL,
    tax_cents INT DEFAULT 0,
    discount_cents INT DEFAULT 0,
    meta JSONB DEFAULT '{}',
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_order_lines_order ON order_lines(order_id);
```

#### Payments
```sql
CREATE TABLE payments (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    order_id UUID NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    provider VARCHAR(100) NOT NULL, -- stripe, paypal, manual
    provider_transaction_id VARCHAR(255),
    status VARCHAR(50) NOT NULL, -- pending, authorized, captured, failed, refunded
    amount_cents INT NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    provider_payload JSONB,
    error_message TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_payments_tenant ON payments(tenant_id);
CREATE INDEX idx_payments_order ON payments(order_id);
CREATE INDEX idx_payments_provider_tx ON payments(provider_transaction_id);
```

#### Shipments
```sql
CREATE TABLE shipments (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    order_id UUID NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    carrier VARCHAR(100), -- ups, fedex, usps, dhl
    service_level VARCHAR(100),
    tracking_number VARCHAR(255),
    status VARCHAR(50) DEFAULT 'pending', -- pending, label_generated, picked_up, in_transit, delivered, failed
    label_url TEXT,
    estimated_delivery_at TIMESTAMP,
    delivered_at TIMESTAMP,
    meta JSONB DEFAULT '{}',
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_shipments_tenant ON shipments(tenant_id);
CREATE INDEX idx_shipments_order ON shipments(order_id);
CREATE INDEX idx_shipments_tracking ON shipments(tracking_number);
```

#### Coupons
```sql
CREATE TABLE coupons (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    code VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL, -- percentage, fixed, free_shipping
    value INT NOT NULL, -- cents or percentage * 100
    currency VARCHAR(3) DEFAULT 'USD',
    usage_limit INT,
    usage_count INT DEFAULT 0,
    minimum_purchase_cents INT,
    conditions JSONB DEFAULT '{}',
    starts_at TIMESTAMP,
    ends_at TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE(tenant_id, code)
);

CREATE INDEX idx_coupons_tenant ON coupons(tenant_id);
CREATE INDEX idx_coupons_code ON coupons(tenant_id, code);
CREATE INDEX idx_coupons_dates ON coupons(starts_at, ends_at);
```

#### Media (Spatie extension)
```sql
-- Spatie media table extended with tenant_id
CREATE TABLE media (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    model_type VARCHAR(255) NOT NULL,
    model_id UUID NOT NULL,
    uuid UUID UNIQUE,
    collection_name VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(255),
    disk VARCHAR(255) NOT NULL,
    conversions_disk VARCHAR(255),
    size BIGINT NOT NULL,
    manipulations JSONB,
    custom_properties JSONB,
    generated_conversions JSONB,
    responsive_images JSONB,
    order_column INT,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_media_tenant ON media(tenant_id);
CREATE INDEX idx_media_model ON media(model_type, model_id);
```

#### Activity Logs
```sql
CREATE TABLE activity_logs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    actor_type VARCHAR(255), -- Employee, Customer, System
    actor_id UUID,
    action VARCHAR(255) NOT NULL,
    target_type VARCHAR(255),
    target_id UUID,
    changes JSONB,
    ip_address INET,
    user_agent TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_activity_logs_tenant ON activity_logs(tenant_id);
CREATE INDEX idx_activity_logs_actor ON activity_logs(actor_type, actor_id);
CREATE INDEX idx_activity_logs_target ON activity_logs(target_type, target_id);
CREATE INDEX idx_activity_logs_created ON activity_logs(created_at);
```

#### Pivot Tables

**Product Categories**
```sql
CREATE TABLE product_category (
    product_id UUID NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    category_id UUID NOT NULL REFERENCES categories(id) ON DELETE CASCADE,
    PRIMARY KEY (product_id, category_id)
);
```

**Product Tags**
```sql
CREATE TABLE product_tag (
    product_id UUID NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    tag_id UUID NOT NULL REFERENCES tags(id) ON DELETE CASCADE,
    PRIMARY KEY (product_id, tag_id)
);
```

## Domain Events & Job Flows

### Event-Driven Architecture

Domain events trigger side effects and maintain consistency across bounded contexts.

#### Key Domain Events

**Catalog Events**
- `ProductCreated`
- `ProductPublished`
- `ProductArchived`
- `VariantCreated`
- `VariantPriceChanged`

**Inventory Events**
- `StockReserved`
- `StockReleased`
- `StockAdjusted`
- `StockLevelLow`
- `StockDepleted`

**Order Events**
- `OrderPlaced`
- `OrderPaid`
- `OrderFulfilled`
- `OrderCompleted`
- `OrderCancelled`
- `OrderRefunded`

**Payment Events**
- `PaymentAuthorized`
- `PaymentCaptured`
- `PaymentFailed`
- `PaymentRefunded`

**Shipment Events**
- `ShipmentCreated`
- `ShipmentLabelGenerated`
- `ShipmentPickedUp`
- `ShipmentDelivered`

### Example Flow: Place Order

```
1. API Request: POST /api/v1/orders
   ↓
2. PlaceOrderHandler (Application Layer)
   - Validate cart items
   - Calculate totals
   - Check stock availability
   ↓
3. Domain: Create Order (status: pending)
   ↓
4. Emit: OrderPlaced Event
   ↓
5. Listeners:
   ├─→ ReserveStockListener → ReserveStockJob
   │   └─→ Creates StockReservation (15 min TTL)
   │   └─→ Emits StockReserved
   │
   ├─→ ProcessPaymentListener → ProcessPaymentJob
   │   └─→ Calls payment gateway
   │   └─→ On success: Emits PaymentCaptured
   │   └─→ On failure: Emits PaymentFailed → ReleaseStockJob
   │
   └─→ SendOrderConfirmationListener → SendEmailJob
       └─→ Queue email to customer
   ↓
6. PaymentCaptured Event
   ↓
7. Listeners:
   ├─→ FinalizeOrderListener
   │   └─→ Update order status to 'paid'
   │   └─→ Convert reservation to committed stock reduction
   │   └─→ Emit OrderPaid
   │
   └─→ CreateShipmentListener → CreateShipmentJob
       └─→ Create Shipment record
       └─→ Emit ShipmentCreated
   ↓
8. ShipmentCreated Event
   ↓
9. GenerateShippingLabelListener → GenerateShippingLabelJob
   └─→ Call carrier API
   └─→ Save label URL
   └─→ Emit ShipmentLabelGenerated
   ↓
10. Update order status to 'processing'
```

### Example Flow: Stock Reservation Expiry

```
1. Scheduled Job: FlushExpiredReservationsJob (runs every 5 min)
   ↓
2. Query: Find reservations where expires_at < now() AND released_at IS NULL
   ↓
3. For each reservation:
   ├─→ Release reserved quantity
   ├─→ Mark reservation as released
   └─→ Emit StockReleased event
   ↓
4. StockReleased Listeners:
   ├─→ UpdateCacheListener → Invalidate stock cache
   └─→ CheckStockLevelListener → If stock was low, may now be available
```

### Example Flow: Product Publish

```
1. API Request: POST /api/v1/products/{id}/publish
   ↓
2. PublishProductHandler
   - Validate product has variants
   - Validate all required fields
   - Change status to 'published'
   - Set published_at timestamp
   ↓
3. Emit: ProductPublished event
   ↓
4. Listeners:
   ├─→ InvalidateProductCacheListener
   │   └─→ Cache::tags(['tenant:X:product:Y'])->flush()
   │
   ├─→ UpdateSearchIndexListener → UpdateSearchIndexJob
   │   └─→ Index product for full-text search
   │
   └─→ RecalculateReportsListener → RecalculateReportsJob
       └─→ Update cached product counts, categories
```

## Background Jobs

### Job Categories

#### 1. **Transactional Jobs** (time-sensitive)
- `ReserveStockJob` - Reserve inventory for order
- `ProcessPaymentJob` - Process payment with gateway
- `ReleaseStockJob` - Release expired reservations
- `SendOrderConfirmationJob` - Send immediate order email

**Queue**: `high` priority, max 3 retries, 5s timeout

#### 2. **Fulfillment Jobs**
- `CreateShipmentJob` - Create shipment record
- `GenerateShippingLabelJob` - Request carrier label
- `UpdateTrackingStatusJob` - Poll carrier for tracking updates
- `SendShipmentNotificationJob` - Notify customer of shipment

**Queue**: `default` priority, max 5 retries, 30s timeout

#### 3. **Notification Jobs**
- `SendEmailJob` - Send templated email
- `SendSMSJob` - Send SMS notification
- `SendPushNotificationJob` - Send mobile push

**Queue**: `low` priority, max 10 retries, 10s timeout

#### 4. **Maintenance Jobs**
- `FlushExpiredReservationsJob` - Release expired stock (scheduled every 5min)
- `PurgeUnusedMediaJob` - Clean orphaned media (scheduled daily)
- `RecalculateReportsJob` - Refresh cached analytics (scheduled hourly/daily)
- `CleanupOldLogsJob` - Archive old activity logs (scheduled weekly)

**Queue**: `maintenance` priority, scheduled via Laravel Scheduler

#### 5. **Integration Jobs**
- `ProcessPaymentWebhookJob` - Handle external webhooks idempotently
- `SyncInventoryJob` - Sync with external ERP (future)
- `ExportOrdersJob` - Generate CSV/PDF exports
- `ImportProductsJob` - Bulk import products from CSV

**Queue**: `default` priority, max 3 retries, 60s timeout

### Job Structure Template

```php
<?php

namespace App\Jobs;

use App\Infrastructure\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExampleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;
    public string $queue = 'default';

    public function __construct(
        public string $tenantId,
        public string $resourceId
    ) {}

    public function handle(): void
    {
        // 1. Set tenant context
        TenantContext::setById($this->tenantId);

        // 2. Perform work
        // ...

        // 3. Emit events if needed
    }

    public function failed(\Throwable $exception): void
    {
        // Log failure, notify admins, etc.
    }
}
```

## Caching Strategy

### Cache Layers

#### 1. **Entity Cache** (TTL: 1 hour)
```php
// Cache single entities by ID
$product = Cache::tags(["tenant:$tenantId", "product:$id"])
    ->remember("tenant:$tenantId:product:$id", 3600, fn() => 
        Product::find($id)
    );
```

#### 2. **Collection Cache** (TTL: 15 minutes)
```php
// Cache filtered/paginated lists
$cacheKey = "tenant:$tenantId:products:page:$page:filters:" . md5(json_encode($filters));
$products = Cache::tags(["tenant:$tenantId", "products"])
    ->remember($cacheKey, 900, fn() => 
        Product::filter($filters)->paginate(20)
    );
```

#### 3. **Computed Cache** (TTL: 1 day)
```php
// Cache expensive aggregations
$salesReport = Cache::tags(["tenant:$tenantId", "reports"])
    ->remember("tenant:$tenantId:sales:$date", 86400, fn() => 
        Order::whereTenant($tenantId)->whereDate('placed_at', $date)->sum('total_cents')
    );
```

### Cache Invalidation Strategy

**Event-driven invalidation**:
```php
// When ProductUpdated event is emitted:
class InvalidateProductCache
{
    public function handle(ProductUpdated $event): void
    {
        $tenantId = $event->product->tenant_id;
        $productId = $event->product->id;
        
        // Invalidate single product
        Cache::tags(["tenant:$tenantId", "product:$productId"])->flush();
        
        // Invalidate product lists
        Cache::tags(["tenant:$tenantId", "products"])->flush();
        
        // Invalidate related caches
        Cache::tags(["tenant:$tenantId", "categories"])->flush();
    }
}
```

### Cache Key Conventions

```
tenant:{tenant_uuid}:entity:{id}
tenant:{tenant_uuid}:collection:page:{n}:filters:{hash}
tenant:{tenant_uuid}:report:{type}:{date}
tenant:{tenant_uuid}:stock:{variant_id}
```

## Authentication & Authorization

### OAuth2 with Laravel Passport

**Grant Types Supported**:
1. **Password Grant** - Employee login (username/password)
2. **Client Credentials** - Machine-to-machine (API integrations)
3. **Refresh Token** - Token refresh
4. **Social Login** (future) - Google, Facebook, Apple

**Token Structure**:
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refresh_token": "def50200...",
  "token_type": "Bearer",
  "expires_in": 3600,
  "tenant_id": "uuid",
  "employee_id": "uuid",
  "scopes": ["read:products", "write:orders"]
}
```

### Spatie Permissions (RBAC)

**Role Hierarchy** (per tenant):
- **Owner**: Full access, manage employees
- **Admin**: Full operational access, limited employee management
- **Manager**: Manage products, orders, inventory
- **Staff**: View-only access, fulfill orders
- **Readonly**: Read-only reporting access

**Permission Naming Convention**:
```
{action}:{resource}

Examples:
- view:products
- create:products
- update:products
- delete:products
- publish:products
- view:orders
- cancel:orders
- refund:orders
- manage:employees
```

**Tenant Scoping**:
```php
// Roles table extended with tenant_id
Schema::table('roles', function (Blueprint $table) {
    $table->uuid('tenant_id')->nullable()->after('id');
    $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
});

// Ensure role assignment respects tenant
Gate::before(function (Employee $employee, $ability) {
    if (TenantContext::get()->id !== $employee->tenant_id) {
        return false; // Cross-tenant access blocked
    }
});
```

## Testing Strategy

### Test Pyramid

```
         ╱╲
        ╱E2E╲         10% - End-to-end (Pest Feature Tests)
       ╱──────╲
      ╱ Integ. ╲       30% - Integration Tests (API endpoints)
     ╱──────────╲
    ╱    Unit    ╲     60% - Unit Tests (Domain logic)
   ╱──────────────╲
```

### Unit Tests (Domain Layer)

Test business logic in isolation:
```php
test('product can be published when valid', function () {
    $product = Product::factory()->make([
        'status' => 'draft',
        'variants_count' => 1
    ]);
    
    $product->publish();
    
    expect($product->status)->toBe('published')
        ->and($product->published_at)->not->toBeNull();
});

test('product cannot be published without variants', function () {
    $product = Product::factory()->make([
        'status' => 'draft',
        'variants_count' => 0
    ]);
    
    $product->publish();
})->throws(DomainException::class, 'Product must have at least one variant');
```

### Integration Tests (API Layer)

Test API endpoints with tenant isolation:
```php
test('employee can create product in own tenant', function () {
    $tenant = Tenant::factory()->create();
    $employee = Employee::factory()->for($tenant)->create();
    
    actingAsTenant($tenant, $employee)
        ->postJson('/api/v1/products', [
            'sku' => 'TEST-001',
            'title' => 'Test Product',
            'status' => 'draft'
        ])
        ->assertCreated()
        ->assertJson(['data' => ['sku' => 'TEST-001']]);
    
    assertDatabaseHas('products', [
        'tenant_id' => $tenant->id,
        'sku' => 'TEST-001'
    ]);
});

test('employee cannot access products from other tenant', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();
    $employee = Employee::factory()->for($tenant1)->create();
    $product = Product::factory()->for($tenant2)->create();
    
    actingAsTenant($tenant1, $employee)
        ->getJson("/api/v1/products/{$product->id}")
        ->assertNotFound();
});
```

### Feature Tests (Business Flows)

Test complete user journeys:
```php
test('complete order placement flow', function () {
    $tenant = Tenant::factory()->create();
    $customer = Customer::factory()->for($tenant)->create();
    $variant = ProductVariant::factory()->for($tenant)->create(['price_cents' => 5000]);
    
    // Reserve stock
    InventoryItem::factory()->for($variant)->create(['stock_on_hand' => 100]);
    
    // Place order
    $response = postJson('/api/v1/orders', [
        'customer_id' => $customer->id,
        'lines' => [
            ['variant_id' => $variant->id, 'quantity' => 2]
        ]
    ])->assertCreated();
    
    $orderId = $response->json('data.id');
    
    // Verify stock reserved
    expect(StockReservation::where('order_id', $orderId)->exists())->toBeTrue();
    
    // Simulate payment webhook
    postJson('/api/v1/webhooks/payment', [
        'order_id' => $orderId,
        'status' => 'paid'
    ])->assertOk();
    
    // Verify order updated
    expect(Order::find($orderId)->status)->toBe('paid');
    
    // Verify shipment created
    expect(Shipment::where('order_id', $orderId)->exists())->toBeTrue();
});
```

### Test Data Factories

Every entity has a factory:
```php
ProductFactory::new()
    ->for($tenant)
    ->hasVariants(3)
    ->published()
    ->create();

OrderFactory::new()
    ->for($tenant)
    ->for($customer)
    ->withLines(5)
    ->paid()
    ->create();
```

### Test Coverage Goals

- **Domain logic**: 100% coverage
- **API endpoints**: 90%+ coverage
- **Job handlers**: 80%+ coverage
- **Overall**: 85%+ coverage

## API Design

### Versioning Strategy

Use URL path versioning: `/api/v1/`, `/api/v2/`

### Authentication

All endpoints require:
```http
Authorization: Bearer {access_token}
X-Tenant-Id: {tenant_uuid} (optional, derived from token)
```

### Response Format

**Success**:
```json
{
  "data": {
    "id": "uuid",
    "type": "product",
    "attributes": { ... }
  },
  "meta": {
    "timestamp": "2025-12-04T10:00:00Z"
  }
}
```

**Error**:
```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid",
    "details": {
      "sku": ["The sku has already been taken."]
    }
  },
  "meta": {
    "timestamp": "2025-12-04T10:00:00Z"
  }
}
```

### Rate Limiting

- **Per Tenant**: 1000 requests/minute
- **Per Employee**: 100 requests/minute
- **Authenticated**: 60 requests/minute
- **Unauthenticated**: 10 requests/minute

### Pagination

```http
GET /api/v1/products?page=2&per_page=20
```

Response includes:
```json
{
  "data": [ ... ],
  "meta": {
    "current_page": 2,
    "per_page": 20,
    "total": 156,
    "last_page": 8
  },
  "links": {
    "first": "/api/v1/products?page=1",
    "prev": "/api/v1/products?page=1",
    "next": "/api/v1/products?page=3",
    "last": "/api/v1/products?page=8"
  }
}
```

## Deployment & Operations

### Environment Variables (Key)

```bash
# App
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.probuy.com

# Database
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=probuy
DB_USERNAME=probuy
DB_PASSWORD=***

# Redis
REDIS_HOST=redis
REDIS_PASSWORD=***
REDIS_PORT=6379

# Queue
QUEUE_CONNECTION=redis
QUEUE_DEFAULT=default

# Cache
CACHE_DRIVER=redis

# Passport
PASSPORT_PRIVATE_KEY=***
PASSPORT_PUBLIC_KEY=***

# Storage
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=***
AWS_SECRET_ACCESS_KEY=***
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=probuy-media

# Spatie Media
MEDIA_DISK=s3
```

### Process Management

**Supervisor Configuration**:
```ini
[program:probuy-queue-default]
command=php /var/www/artisan queue:work redis --queue=default --tries=3 --timeout=30
process_name=%(program_name)s_%(process_num)02d
numprocs=4
autostart=true
autorestart=true
user=www-data

[program:probuy-queue-high]
command=php /var/www/artisan queue:work redis --queue=high --tries=3 --timeout=10
process_name=%(program_name)s_%(process_num)02d
numprocs=2
autostart=true
autorestart=true
user=www-data

[program:probuy-scheduler]
command=/bin/sh -c "while [ true ]; do (php /var/www/artisan schedule:run --verbose --no-interaction &); sleep 60; done"
autostart=true
autorestart=true
user=www-data
```

### Scaling Considerations

#### Horizontal Scaling
- **API servers**: Stateless, scale via load balancer
- **Queue workers**: Scale by increasing `numprocs` or adding worker nodes
- **Database**: Postgres read replicas for reports/analytics

#### Vertical Scaling
- **Redis**: Single instance with persistence, or Redis Cluster for HA
- **Postgres**: Scale compute (CPU/RAM) before sharding

#### Future Optimizations
- **CDN**: CloudFront for media assets
- **Read replicas**: Separate reporting queries
- **Database sharding**: Shard by `tenant_id` if single DB becomes bottleneck
- **Microservices**: Extract high-load contexts (Catalog, Orders) into separate services

### Monitoring & Observability

**Metrics to Track**:
- Request latency (p50, p95, p99)
- Error rates by endpoint
- Queue depth & processing time
- Database connection pool usage
- Cache hit rate
- Order conversion rate
- Stock reservation expiry rate

**Tools**:
- Laravel Horizon (queue monitoring)
- Laravel Telescope (debugging)
- Sentry (error tracking)
- New Relic / DataDog (APM)
- Prometheus + Grafana (metrics)

## Security Considerations

### Checklist

✅ **Tenant Isolation**: All queries scoped, tests verify no cross-tenant access  
✅ **SQL Injection**: Eloquent ORM, prepared statements  
✅ **XSS**: API-only, no HTML rendering  
✅ **CSRF**: Not applicable (API-only, token auth)  
✅ **Rate Limiting**: Implemented per tenant/employee  
✅ **Authentication**: OAuth2 with JWT  
✅ **Authorization**: Spatie Permissions RBAC  
✅ **Audit Logging**: All mutations logged with actor  
✅ **Encryption**: Sensitive data encrypted at rest (Laravel encryption)  
✅ **HTTPS**: TLS 1.3 enforced  
✅ **Secrets Management**: Environment variables, never committed  
✅ **Dependency Scanning**: `composer audit` in CI  
✅ **Input Validation**: Form Requests with strict rules  

### Threat Model

| Threat | Mitigation |
|--------|-----------|
| Cross-tenant data access | Global scopes, middleware, integration tests |
| Unauthorized API access | OAuth2 tokens, permission checks |
| Mass assignment | `$fillable` / `$guarded` on models |
| Payment fraud | Idempotent webhooks, amount verification |
| Inventory race conditions | Database transactions, locks |
| DDoS | Rate limiting, CloudFlare |
| Leaked credentials | Secrets in env, rotate regularly |

## Future Roadmap

### Phase 2 Features
- [ ] Multi-storefront (one tenant, multiple branded stores)
- [ ] Marketplace seller splitting
- [ ] Advanced tax engine (Avalara/TaxJar integration)
- [ ] Gift cards & store credit
- [ ] Subscription products
- [ ] Customer loyalty/rewards program

### Phase 3 Features
- [ ] Multi-language support (i18n)
- [ ] Multi-currency pricing
- [ ] Warehouse management (multiple locations)
- [ ] B2B wholesale pricing tiers
- [ ] Advanced analytics dashboard
- [ ] Mobile app (React Native)

### Technical Debt
- [ ] Migrate to database-per-tenant if tenant count grows
- [ ] Implement Postgres Row-Level Security
- [ ] Extract search to Elasticsearch/Meilisearch
- [ ] Implement GraphQL API alongside REST
- [ ] Add real-time WebSocket updates (Laravel Echo)

---

## Appendix

### Glossary

- **Aggregate Root**: Main entity that controls a cluster of related entities
- **Bounded Context**: A logical boundary within which a domain model is defined
- **DDD**: Domain-Driven Design - software design approach focusing on business domains
- **RBAC**: Role-Based Access Control
- **TTL**: Time To Live (expiration duration)
- **Value Object**: Immutable object defined by its attributes, no identity

### References

- [Laravel Documentation](https://laravel.com/docs)
- [Domain-Driven Design by Eric Evans](https://www.domainlanguage.com/ddd/)
- [Spatie MediaLibrary](https://spatie.be/docs/laravel-medialibrary)
- [Spatie Permissions](https://spatie.be/docs/laravel-permission)
- [Laravel Passport](https://laravel.com/docs/passport)

---

**Document Version**: 1.0  
**Last Updated**: December 4, 2025  
**Maintainers**: Engineering Team

