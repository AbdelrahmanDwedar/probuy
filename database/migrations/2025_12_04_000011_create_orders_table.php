<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->onDelete('cascade');
            $table->string('order_number');
            $table->foreignUuid('customer_id')->nullable()->constrained()->onDelete('set null');
            $table->string('currency', 3)->default('USD');
            $table->integer('subtotal_cents')->default(0);
            $table->integer('tax_cents')->default(0);
            $table->integer('shipping_cents')->default(0);
            $table->integer('discount_cents')->default(0);
            $table->integer('total_cents')->default(0);
            $table->string('status', 50)->default('pending');
            $table->jsonb('billing_address')->nullable();
            $table->jsonb('shipping_address')->nullable();
            $table->text('customer_note')->nullable();
            $table->jsonb('metadata')->default('{}');
            $table->timestamp('placed_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'order_number']);
            $table->index(['tenant_id', 'id']);
            $table->index('customer_id');
            $table->index(['tenant_id', 'status']);
            $table->index('placed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

