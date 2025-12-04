<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('order_id')->constrained()->onDelete('cascade');
            $table->string('carrier', 100)->nullable();
            $table->string('service_level', 100)->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('status', 50)->default('pending');
            $table->text('label_url')->nullable();
            $table->timestamp('estimated_delivery_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->jsonb('meta')->default('{}');
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('order_id');
            $table->index('tracking_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};

