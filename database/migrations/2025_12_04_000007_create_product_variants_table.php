<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('tenant_id')->constrained()->onDelete('cascade');
            $table->string('sku');
            $table->integer('price_cents')->default(0);
            $table->integer('compare_at_price_cents')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->integer('cost_cents')->nullable();
            $table->jsonb('attributes')->default('{}');
            $table->integer('weight_grams')->nullable();
            $table->jsonb('dimensions')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'sku']);
            $table->index(['tenant_id', 'id']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};

