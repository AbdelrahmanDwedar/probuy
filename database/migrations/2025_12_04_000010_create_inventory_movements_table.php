<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('inventory_item_id')->constrained()->onDelete('cascade');
            $table->integer('delta');
            $table->string('reason');
            $table->string('reference_type')->nullable();
            $table->uuid('reference_id')->nullable();
            $table->uuid('actor_id')->nullable();
            $table->jsonb('meta')->default('{}');
            $table->timestamp('created_at');

            $table->index('tenant_id');
            $table->index('inventory_item_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};

