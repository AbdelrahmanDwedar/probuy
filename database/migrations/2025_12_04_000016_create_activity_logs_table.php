<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->onDelete('cascade');
            $table->string('actor_type')->nullable();
            $table->uuid('actor_id')->nullable();
            $table->string('action');
            $table->string('target_type')->nullable();
            $table->uuid('target_id')->nullable();
            $table->jsonb('changes')->nullable();
            $table->inet('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at');

            $table->index('tenant_id');
            $table->index(['actor_type', 'actor_id']);
            $table->index(['target_type', 'target_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};

