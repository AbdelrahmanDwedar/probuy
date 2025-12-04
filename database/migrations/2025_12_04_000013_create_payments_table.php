<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('order_id')->constrained()->onDelete('cascade');
            $table->string('provider', 100);
            $table->string('provider_transaction_id')->nullable();
            $table->string('status', 50);
            $table->integer('amount_cents');
            $table->string('currency', 3)->default('USD');
            $table->jsonb('provider_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('order_id');
            $table->index('provider_transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

