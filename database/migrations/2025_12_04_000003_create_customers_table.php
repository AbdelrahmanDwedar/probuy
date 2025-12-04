<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->onDelete('cascade');
            $table->string('email')->nullable();
            $table->string('name')->nullable();
            $table->string('phone', 50)->nullable();
            $table->jsonb('addresses')->default('[]');
            $table->jsonb('meta')->default('{}');
            $table->timestamps();

            $table->index(['tenant_id', 'id']);
            $table->index(['tenant_id', 'email']);
        });

        // Add unique constraint only for non-null emails
        DB::statement('CREATE UNIQUE INDEX customers_tenant_email_unique ON customers (tenant_id, email) WHERE email IS NOT NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
