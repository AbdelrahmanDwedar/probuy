<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('product_variant_id')->constrained()->onDelete('cascade');
            $table->integer('stock_on_hand')->default(0);
            $table->integer('stock_reserved')->default(0);
            $table->integer('low_stock_threshold')->default(10);
            $table->string('warehouse_location')->nullable();
            $table->jsonb('meta')->default('{}');
            $table->timestamps();

            $table->unique(['tenant_id', 'product_variant_id']);
            $table->index('tenant_id');
            $table->index('product_variant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};

