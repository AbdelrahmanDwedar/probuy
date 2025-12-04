<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_reservations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('inventory_item_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('order_id')->nullable()->constrained()->onDelete('cascade');
            $table->integer('quantity');
            $table->string('reason')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('inventory_item_id');
            $table->index('expires_at');
        });

        // Index for finding expired reservations
        DB::statement('CREATE INDEX stock_reservations_expired ON stock_reservations (expires_at) WHERE released_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_reservations');
    }
};

