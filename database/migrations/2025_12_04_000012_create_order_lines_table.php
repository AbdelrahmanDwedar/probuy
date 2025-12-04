<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('product_variant_id')->nullable()->constrained()->onDelete('set null');
            $table->string('sku');
            $table->string('title', 500);
            $table->integer('quantity');
            $table->integer('price_cents');
            $table->integer('tax_cents')->default(0);
            $table->integer('discount_cents')->default(0);
            $table->jsonb('meta')->default('{}');
            $table->timestamps();

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_lines');
    }
};

