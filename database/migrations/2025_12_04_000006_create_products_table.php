<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->onDelete('cascade');
            $table->string('sku');
            $table->string('title', 500);
            $table->text('description')->nullable();
            $table->string('status', 50)->default('draft');
            $table->jsonb('metadata')->default('{}');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'sku']);
            $table->index(['tenant_id', 'id']);
            $table->index(['tenant_id', 'status']);
        });

        // GIN index for JSONB metadata
        DB::statement('CREATE INDEX products_metadata_gin ON products USING GIN (metadata)');
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

