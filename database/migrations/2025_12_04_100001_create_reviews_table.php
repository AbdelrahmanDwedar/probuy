<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('product_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('customer_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('order_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('rating')->unsigned(); // 1-5
            $table->string('title')->nullable();
            $table->text('comment')->nullable();
            $table->boolean('is_verified_purchase')->default(false);
            $table->boolean('is_approved')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'product_id']);
            $table->index(['tenant_id', 'customer_id']);
            $table->index('rating');
            $table->unique(['tenant_id', 'product_id', 'customer_id', 'order_id']);
        });

        // Add average rating cache to products
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('average_rating', 3, 2)->nullable()->after('metadata');
            $table->integer('review_count')->default(0)->after('average_rating');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['average_rating', 'review_count']);
        });
        
        Schema::dropIfExists('reviews');
    }
};

